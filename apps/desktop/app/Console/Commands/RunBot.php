<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Desktop\Bots\QueuedTasks;
use Lorekeeper\Apps\Desktop\Bots\RunRecorder;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Session\Session;
use Lorekeeper\Client\Bot\Session\Watch;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Core\Player\PlayerName;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'bots:run', description: 'Keep one bot in the game and play its tasks until it is stopped')]
final class RunBot extends Command
{
    /** @var string */
    protected $signature = 'bots:run {run}';

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

        $stop = Watch::until(static fn(): bool => $run->fresh()?->status !== RunStatus::Running);

        try {
            $session = new Session(
                new Credentials(new PlayerName($run->account->name), $run->account->password, $run->account->server),
                new RunRecorder($run),
                $logins,
            );
            $ending = $session->play(new QueuedTasks($run), $stop->token());
        } finally {
            $stop->release();
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
