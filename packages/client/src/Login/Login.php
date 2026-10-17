<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Login;

use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Exception\PeerClosedException;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Game\EnterGameScript;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\NullCancellationToken;
use SensitiveParameter;
use Throwable;

final readonly class Login
{
    public static function enter(
        Credentials $credentials,
        CancellationTokenInterface $stop = new NullCancellationToken(),
        LoginClient $logins = new LoginClient(),
        ClientVersion $version = new ClientVersion('5.1'),
    ): Client|Ending {
        try {
            $login = $logins->login($credentials->player, $credentials->password);

            $server = ServerChoice::pick($login->servers, $credentials->server);
            if ($server->isNone()) {
                return new Ending(EndingStatus::Failed, 'no open server');
            }

            return self::enterOn(
                Connection::open($server->unwrap()),
                $credentials->player,
                $login->token,
                $stop,
                $version,
            );
        } catch (Throwable $failure) {
            return new Ending(EndingStatus::Failed, $failure->getMessage());
        }
    }

    public static function enterOn(
        Connection $connection,
        PlayerName $player,
        #[SensitiveParameter]
        string $token,
        CancellationTokenInterface $stop = new NullCancellationToken(),
        ClientVersion $version = new ClientVersion('5.1'),
    ): Client|Ending {
        $client = new Client($connection, new Character($player));

        $entered = $client->run(new EnterGameScript(new GameLogin($player, $token, $version)), $stop);
        if ($entered instanceof Disconnected) {
            return new Ending(EndingStatus::Disconnected, $entered->reason);
        }

        if (!$entered instanceof InGame) {
            $client->close();

            return $entered instanceof Stopped
                ? new Ending(EndingStatus::Stopped)
                : new Ending(EndingStatus::Failed, Outcome::of($entered)->summary);
        }

        $client->character()->entered($entered);

        return $client;
    }

    public static function leave(Client $client): bool
    {
        try {
            $client->send(new LogoutCommand());

            return true;
        } catch (PeerClosedException) {
            return false;
        } finally {
            $client->close();
        }
    }
}
