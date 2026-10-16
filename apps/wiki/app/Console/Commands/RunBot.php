<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Wiki\Bots\EventSummary;
use Lorekeeper\Apps\Wiki\Bots\LogLevel;
use Lorekeeper\Apps\Wiki\Bots\Position;
use Lorekeeper\Apps\Wiki\Bots\RunRecorder;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Bots\Scripts\Done;
use Lorekeeper\Apps\Wiki\Bots\Scripts\WaitScript;
use Lorekeeper\Apps\Wiki\Bots\TaskStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotTask;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Client\Login\ServerChoice;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Game\EnterGameScript;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Game\LogoutScript;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Psl\Async;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
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

        try {
            [$status, $result] = $this->session($run, $logins, $stop);
        } catch (Throwable $failure) {
            [$status, $result] = [RunStatus::Failed, $failure->getMessage()];
        } finally {
            foreach ($signals as $signal) {
                Scheduler::cancel($signal);
            }
        }

        $run->closeTasks('the run ended');
        $run->update(['status' => $status, 'result' => $result, 'pid' => null, 'finished_at' => now()]);
        $run->log(...self::ending($run));

        return self::SUCCESS;
    }

    /** @return array{RunStatus, null|string} */
    private function session(BotRun $run, LoginClient $logins, SignalCancellationToken $stop): array
    {
        $player = new PlayerName($run->account->name);

        $run->log("Logging in as {$run->account->name}");
        $login = $logins->login($player, $run->account->password);

        $server = ServerChoice::pick($login->servers, $run->account->server);
        if ($server->isNone()) {
            return [RunStatus::Failed, 'no open server'];
        }

        $run->log("Connecting to {$server->unwrap()->name}");
        $connection = Connection::open($server->unwrap());
        $recorder = new RunRecorder($run);
        $client = new Client($connection, $recorder->heard(...), $recorder->stepped(...));

        $entered = $client->run(
            new EnterGameScript(new GameLogin($player, $login->token, new ClientVersion('5.1'))),
            $stop,
        );
        if ($entered instanceof Disconnected) {
            return [RunStatus::Disconnected, $entered->reason];
        }

        if (!$entered instanceof InGame) {
            $connection->close();

            return (
                $entered instanceof Stopped ? [RunStatus::Stopped, null] : [RunStatus::Failed, self::reason($entered)]
            );
        }

        $recorder->at(new Position($entered->map->map, $entered->map->areaId));

        $waiting = false;
        while (!$stop->isCancelled()) {
            $task = $run->nextTask();

            if ($task === null) {
                if (!$waiting) {
                    $waiting = true;
                    $recorder->log("Waiting for tasks in {$run->refresh()->map}");
                }

                $waited = $client->run(new WaitScript(Duration::seconds(2)), $stop);
                if ($waited instanceof Disconnected) {
                    return [RunStatus::Disconnected, $waited->reason];
                }

                continue;
            }

            $waiting = false;
            $played = $this->play($client, $recorder, $player, $task, $stop);
            if ($played instanceof Disconnected) {
                return [RunStatus::Disconnected, $played->reason];
            }
        }

        $client->run(new LogoutScript());
        $connection->close();

        return [RunStatus::Stopped, null];
    }

    private function play(
        Client $client,
        RunRecorder $recorder,
        PlayerName $player,
        BotTask $task,
        SignalCancellationToken $stop,
    ): ?Disconnected {
        $task->update(['status' => TaskStatus::Running, 'started_at' => now()]);
        $recorder->working($task);
        $recorder->log("Task #{$task->id}: {$task->title()}");

        $cancel = new SignalCancellationToken();
        $done = false;
        $watcher = Async\run(static function () use (&$done, $task, $cancel): void {
            while (!$done) {
                Async\sleep(Duration::seconds(1));
                if (!$done && $task->fresh()?->status === TaskStatus::Cancelling) {
                    $cancel->cancel();

                    return;
                }
            }
        });

        try {
            $here = $recorder->position();
            $result = $here === null
                ? 'the bot does not know where it is'
                : $client->run(
                    $task->script->script($player, $here, $task->parameters),
                    new Async\LinkedCancellationToken($stop, $cancel),
                );
        } catch (Throwable $failure) {
            $result = $failure->getMessage();
        } finally {
            $done = true;
            $watcher->await();
        }

        $outcome = match (true) {
            $result instanceof Done => [TaskStatus::Done, $result->summary, LogLevel::Success],
            $result instanceof Disconnected => [TaskStatus::Failed, 'disconnected', LogLevel::Error],
            $result instanceof Stopped && $stop->isCancelled() => [
                TaskStatus::Cancelled,
                'the run stopped',
                LogLevel::Warning,
            ],
            $result instanceof Stopped => [TaskStatus::Cancelled, 'cancelled', LogLevel::Warning],
            default => [TaskStatus::Failed, self::reason($result), LogLevel::Error],
        };

        $task->finish($outcome[0], $outcome[1]);
        $recorder->log("Task #{$task->id} {$outcome[0]->value}: {$outcome[1]}", $outcome[2]);
        $recorder->working(null);

        return $result instanceof Disconnected ? $result : null;
    }

    private static function reason(mixed $result): string
    {
        return match (true) {
            is_string($result) => $result,
            $result instanceof TimedOut => 'no answer while waiting for '
                . implode(', ', array_map(static fn(string $event): string => substr(
                    $event,
                    (int) strrpos($event, needle: '\\') + 1,
                ), $result->await->events)),
            $result instanceof Event => EventSummary::of($result),
            default => 'the script ended',
        };
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
