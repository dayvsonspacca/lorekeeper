<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Desktop\Bots\BotRecorder;
use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Bots\HideFromPlayers;
use Lorekeeper\Apps\Desktop\Bots\LogLevel;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notice;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Lorekeeper\Apps\Desktop\Bots\PlayScript;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Apps\Desktop\Tauri\Shell;
use Lorekeeper\Client\Api\Login\LoginClient;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\OutcomeStatus;
use Lorekeeper\Client\Script\Watch;
use Lorekeeper\Client\Session\Credentials;
use Lorekeeper\Client\Session\Ending;
use Lorekeeper\Client\Session\EndingStatus;
use Lorekeeper\Client\Session\Login;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\CancellationTokenInterface;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(
    name: 'bots:play',
    description: 'Keep one character in the game, playing the script it is given, until it is stopped',
)]
/** @mago-expect lint:cyclomatic-complexity */
final class PlayBot extends Command
{
    /** @var string */
    protected $signature = 'bots:play {account}';

    public function handle(LoginClient $logins, Notifier $notifier): int
    {
        $account = Account::query()->findOrFail($this->argument('account'));

        $claimed = Account::query()
            ->whereKey($account->id)
            ->where('status', BotStatus::Starting)
            ->update([
                'status' => BotStatus::Online,
                'pid' => getmypid(),
                'started_at' => now(),
                'stopped_at' => null,
                'last_server' => null,
            ]);
        if ($claimed === 0) {
            if ($account->refresh()->status === BotStatus::Stopping && $account->settle(BotStatus::Offline, null)) {
                $account->log('Stopped before it entered the game');
            }

            return self::SUCCESS;
        }

        $account->refresh();
        $recorder = new BotRecorder($account);
        $stop = Watch::until(static fn(): bool => $account->fresh()?->status !== BotStatus::Online || !Shell::alive());

        try {
            $account->log("Logging in as {$account->name}");
            $client = Login::enter(
                new Credentials(new PlayerName($account->name), $account->password, $account->server),
                $stop->token(),
                $logins,
            );
            $ending = $client instanceof Client ? self::stay($account, $client, $recorder, $stop->token()) : $client;
        } catch (Throwable $exception) {
            report($exception);
            $ending = new Ending(
                EndingStatus::Failed,
                $exception->getMessage() !== '' ? $exception->getMessage() : $exception::class,
            );
        } finally {
            $stop->release();
        }

        if (!Account::query()->whereKey($account->id)->exists()) {
            return self::SUCCESS;
        }

        if ($ending->status === EndingStatus::Stopped && !Shell::alive()) {
            $ending = new Ending(EndingStatus::Stopped, 'the app closed');
        }

        $settled = $account->settle(match ($ending->status) {
            EndingStatus::Stopped => BotStatus::Offline,
            EndingStatus::Disconnected => BotStatus::Disconnected,
            EndingStatus::Failed => BotStatus::Failed,
        }, $ending->reason);
        if ($settled) {
            $account->log(...self::ending($account));
            $notice = Notice::leaving($account);
            if ($notice !== null) {
                $notifier->show($notice);
            }
        }

        return self::SUCCESS;
    }

    private static function stay(
        Account $account,
        Client $client,
        BotRecorder $recorder,
        CancellationTokenInterface $stop,
    ): Ending {
        $client->subscribe($recorder->heard(...));
        $client->character()->subscribe($recorder);
        $recorder->update($client->character());
        $account->update(['last_server' => $client->server()?->name]);
        $account->log(
            "Entered the game in {$client->character()->location()->map->name->value}"
            . ($account->last_server === null ? '' : " on {$account->last_server}"),
            LogLevel::Success,
        );
        $hiding = HideFromPlayers::apply(Preferences::current(), $account, $client, $stop);
        if ($hiding instanceof Disconnected) {
            return new Ending(EndingStatus::Disconnected, $hiding->reason);
        }

        while (!$stop->isCancelled()) {
            $played = PlayScript::next($account, $client, $stop);
            if ($played?->status === OutcomeStatus::Disconnected) {
                return new Ending(EndingStatus::Disconnected, $played->summary);
            }

            if ($played !== null) {
                continue;
            }

            $waited = $client->wait(Duration::seconds(2), $stop);
            if ($waited instanceof Disconnected) {
                return new Ending(EndingStatus::Disconnected, $waited->reason);
            }
        }

        Login::leave($client);

        return new Ending(EndingStatus::Stopped);
    }

    /** @return array{string, LogLevel} */
    private static function ending(Account $account): array
    {
        return match ($account->status) {
            BotStatus::Disconnected => ["Disconnected: {$account->result}", LogLevel::Error],
            BotStatus::Failed => ["Failed: {$account->result}", LogLevel::Error],
            default => ["Stopped after {$account->duration()}", LogLevel::Info],
        };
    }
}
