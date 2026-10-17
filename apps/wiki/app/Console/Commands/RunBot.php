<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Wiki\Bots\RunRecorder;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Login\Ending;
use Lorekeeper\Client\Login\EndingStatus;
use Lorekeeper\Client\Login\Login;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Client\Narrator;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'bots:run', description: 'Keep one bot in the game, recording what it hears, until it is stopped')]
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
            $client = Login::enter(
                new Credentials(new PlayerName($run->account->name), $run->account->password, $run->account->server),
                new Narrator($recorder),
                $stop,
                $logins,
            );
            $ending = $client instanceof Client ? $this->stay($run, $client, $stop) : $client;
        } finally {
            foreach ($signals as $signal) {
                Scheduler::cancel($signal);
            }
        }

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

    private function stay(BotRun $run, Client $client, CancellationTokenInterface $stop): Ending
    {
        $run->log("Listening in {$client->character()->position()->map->value}");

        while (!$stop->isCancelled()) {
            $waited = $client->wait(Duration::minutes(1), $stop);
            if ($waited instanceof Disconnected) {
                return new Ending(EndingStatus::Disconnected, $waited->reason);
            }
        }

        Login::leave($client);

        return new Ending(EndingStatus::Stopped);
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
