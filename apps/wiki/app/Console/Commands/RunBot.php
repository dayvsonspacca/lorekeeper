<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Lorekeeper\Apps\Wiki\Bots\RunRecorder;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Bots\TaskStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotTask;
use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Session\Ending;
use Lorekeeper\Client\Bot\Session\OutcomeStatus;
use Lorekeeper\Client\Bot\Session\Session;
use Lorekeeper\Client\Bot\Session\SessionStatus;
use Lorekeeper\Client\Bot\Session\Watch;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\LinkedCancellationToken;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;

/** @mago-expect lint:kan-defect */
#[AsCommand(name: 'bots:run', description: 'Keep one bot in the game and play its tasks until it is stopped')]
final class RunBot extends Command
{
    /** @var string */
    protected $signature = 'bots:run {run}';

    public function handle(LoginClient $logins): int
    {
        $run = BotRun::query()->with('account')->findOrFail($this->argument('run'));
        $run->update(['status' => RunStatus::Running, 'pid' => getmypid(), 'started_at' => now()]);

        $stop = new SignalCancellationToken();
        $signals = [];
        foreach ([SIGTERM, SIGINT] as $signal) {
            $signals[] = Scheduler::onSignal($signal, static function () use ($stop): void {
                $stop->cancel();
            });
        }

        $recorder = new RunRecorder($run);

        try {
            $session = Session::enter(
                new Credentials(new PlayerName($run->account->name), $run->account->password, $run->account->server),
                $recorder,
                $stop,
                $logins,
            );
            $ending = $session instanceof Session ? $this->playTasks($run, $session, $recorder, $stop) : $session;
        } finally {
            foreach ($signals as $signal) {
                Scheduler::cancel($signal);
            }
        }

        $run->closeTasks('the run ended');
        $run->update([
            'status' => match ($ending->status) {
                SessionStatus::Stopped => RunStatus::Stopped,
                SessionStatus::Disconnected => RunStatus::Disconnected,
                SessionStatus::Failed => RunStatus::Failed,
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
        Session $session,
        RunRecorder $recorder,
        CancellationTokenInterface $stop,
    ): Ending {
        $waiting = false;
        while (!$stop->isCancelled()) {
            $task = $run->nextTask();

            if ($task === null) {
                if (!$waiting) {
                    $waiting = true;
                    $run->log("Waiting for tasks in {$session->character()->position()->map->value}");
                }

                if (!$session->wait(Duration::seconds(2), $stop)) {
                    return new Ending(SessionStatus::Disconnected, 'the connection dropped');
                }

                continue;
            }

            $waiting = false;
            $this->play($run, $session, $recorder, $task, $stop);
            if (!$session->connected()) {
                return new Ending(SessionStatus::Disconnected, 'the connection dropped');
            }
        }

        $session->leave();

        return new Ending(SessionStatus::Stopped);
    }

    private function play(
        BotRun $run,
        Session $session,
        RunRecorder $recorder,
        BotTask $task,
        CancellationTokenInterface $stop,
    ): void {
        try {
            $script = $task->script->task($task->parameters);
        } catch (InvalidArgumentException $invalid) {
            $task->finish(TaskStatus::Failed, $invalid->getMessage());
            $run->log("Task #{$task->id} failed: {$invalid->getMessage()}", LogLevel::Error, $task);

            return;
        }

        $task->update(['status' => TaskStatus::Running, 'started_at' => now()]);
        $recorder->working($task);
        $cancel = Watch::until(static fn(): bool => $task->fresh()?->status !== TaskStatus::Running);

        try {
            $outcome = $session->play($script, new LinkedCancellationToken($stop, $cancel->token()));
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
