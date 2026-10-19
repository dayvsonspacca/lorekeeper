<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Lorekeeper\Apps\Desktop\Bots\LogLevel;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\TaskNotice;
use Lorekeeper\Apps\Desktop\Bots\RunRecorder;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Client\Api\Login\LoginClient;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\OutcomeStatus;
use Lorekeeper\Client\Script\Watch;
use Lorekeeper\Client\Session\Credentials;
use Lorekeeper\Client\Session\Ending;
use Lorekeeper\Client\Session\EndingStatus;
use Lorekeeper\Client\Session\Login;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\LinkedCancellationToken;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;

/** @mago-expect lint:kan-defect */
#[AsCommand(name: 'bots:run', description: 'Keep one bot in the game and play its tasks until it is stopped')]
final class RunBot extends Command
{
    /** @var string */
    protected $signature = 'bots:run {run}';

    public function __construct(
        private readonly Notifier $notifier,
    ) {
        parent::__construct();
    }

    public function handle(LoginClient $logins): int
    {
        $run = BotRun::query()->with('account')->findOrFail($this->argument('run'));

        $claimed = BotRun::query()
            ->whereKey($run->id)
            ->where('status', RunStatus::Pending)
            ->update(['status' => RunStatus::Running, 'pid' => getmypid(), 'started_at' => now()]);
        if ($claimed === 0) {
            if ($run->refresh()->status === RunStatus::Stopping) {
                $run->update(['status' => RunStatus::Stopped, 'finished_at' => now()]);
                $run->log('Stopped before it started');
            }

            return self::SUCCESS;
        }

        $run->refresh();
        $recorder = new RunRecorder($run);
        $stop = Watch::until(static fn(): bool => $run->fresh()?->status !== RunStatus::Running);

        try {
            $run->log("Logging in as {$run->account->name}");
            $client = Login::enter(
                new Credentials(new PlayerName($run->account->name), $run->account->password, $run->account->server),
                $stop->token(),
                $logins,
            );
            $ending = $client instanceof Client ? $this->playTasks($run, $client, $recorder, $stop->token()) : $client;
        } finally {
            $stop->release();
        }

        $run->closeTasks('the run ended');
        $run->update([
            'status' => match ($ending->status) {
                EndingStatus::Stopped => RunStatus::Stopped,
                EndingStatus::Disconnected => RunStatus::Disconnected,
                EndingStatus::Failed => RunStatus::Failed,
            },
            'result' => $ending->reason,
            'pid' => null,
            'finished_at' => now(),
        ]);
        $run->log(...self::ending($run));

        return self::SUCCESS;
    }

    private function playTasks(
        BotRun $run,
        Client $client,
        RunRecorder $recorder,
        CancellationTokenInterface $stop,
    ): Ending {
        $client->subscribe($recorder->heard(...));
        $client->character()->subscribe($recorder);
        $recorder->update($client->character());
        $run->log("Entered the game in {$client->character()->position()->map->value}", LogLevel::Success);

        $waiting = false;
        while (!$stop->isCancelled()) {
            $task = $run->nextTask();

            if ($task === null) {
                if (!$waiting) {
                    $waiting = true;
                    $run->log("Waiting for tasks in {$client->character()->position()->map->value}");
                }

                $waited = $client->wait(Duration::seconds(2), $stop);
                if ($waited instanceof Disconnected) {
                    return new Ending(EndingStatus::Disconnected, $waited->reason);
                }

                continue;
            }

            $waiting = false;
            $outcome = $this->play($run, $client, $recorder, $task, $stop);
            if ($outcome?->status === OutcomeStatus::Disconnected) {
                return new Ending(EndingStatus::Disconnected, $outcome->summary);
            }
        }

        Login::leave($client);

        return new Ending(EndingStatus::Stopped);
    }

    private function play(
        BotRun $run,
        Client $client,
        RunRecorder $recorder,
        BotTask $task,
        CancellationTokenInterface $stop,
    ): ?Outcome {
        try {
            $script = $task->script->script($task->parameters);
        } catch (InvalidArgumentException $invalid) {
            $task->finish(TaskStatus::Failed, $invalid->getMessage());
            $run->log("Task #{$task->id} failed: {$invalid->getMessage()}", LogLevel::Error, $task);
            $this->announce($task);

            return null;
        }

        $task->update([
            'status' => TaskStatus::Running,
            'started_at' => now(),
            'level_from' => $run->account->refresh()->level,
        ]);
        $recorder->working($task);
        $recorder->update($client->character());
        $cancel = Watch::until(static fn(): bool => $task->fresh()?->status !== TaskStatus::Running);

        try {
            $run->log("Playing {$script->title()}", LogLevel::Info, $task);
            $outcome = Outcome::of($client->run($script, new LinkedCancellationToken($stop, $cancel->token())));
        } finally {
            $cancel->release();
            $recorder->working(null);
        }

        $task->finish(
            match ($outcome->status) {
                OutcomeStatus::Done => TaskStatus::Done,
                OutcomeStatus::Cancelled => TaskStatus::Cancelled,
                OutcomeStatus::Failed, OutcomeStatus::Disconnected => TaskStatus::Failed,
            },
            $outcome->status === OutcomeStatus::Cancelled && $stop->isCancelled()
                ? 'the run stopped'
                : $outcome->summary,
        );
        $run->log(
            "{$script->title()}: {$outcome->summary}",
            match ($outcome->status) {
                OutcomeStatus::Done => LogLevel::Success,
                OutcomeStatus::Cancelled => LogLevel::Warning,
                OutcomeStatus::Failed, OutcomeStatus::Disconnected => LogLevel::Error,
            },
            $task,
        );
        $this->announce($task);

        return $outcome;
    }

    private function announce(BotTask $task): void
    {
        $notice = TaskNotice::of($task);
        if ($notice !== null) {
            $this->notifier->show($notice);
        }
    }

    /** @return array{string, LogLevel} */
    private static function ending(BotRun $run): array
    {
        return match ($run->status) {
            RunStatus::Disconnected => ["Disconnected: {$run->result}", LogLevel::Error],
            RunStatus::Failed => ["Failed: {$run->result}", LogLevel::Error],
            default => ["Stopped after {$run->duration()}", LogLevel::Info],
        };
    }
}
