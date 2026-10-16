<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Wiki\Bots\QueuedTasks;
use Lorekeeper\Apps\Wiki\Bots\RunRecorder;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Session\Session;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Symfony\Component\Console\Attribute\AsCommand;

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
            $session = new Session(
                new Credentials(new PlayerName($run->account->name), $run->account->password, $run->account->server),
                new RunRecorder($run),
                $logins,
            );
            $ending = $session->play(new QueuedTasks($run), $stop);
        } finally {
            foreach ($signals as $signal) {
                Scheduler::cancel($signal);
            }
        }

        $run->closeTasks('the run ended');
        $run->update([
            'status' => RunStatus::from($ending->status->value),
            'result' => $ending->reason,
            'pid' => null,
            'finished_at' => now(),
        ]);
        $run->log(...self::ending($run));

        return self::SUCCESS;
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
