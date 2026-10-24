<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

use Lorekeeper\Client\Api\Login\LoginClient;
use Lorekeeper\Client\Api\Server\ServerChoice;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\PeerClosedException;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\NullCancellationToken;
use SensitiveParameter;
use Throwable;

final readonly class Login
{
    public const string VERSION = '5.3';

    public static function enter(
        Credentials $credentials,
        CancellationTokenInterface $stop = new NullCancellationToken(),
        LoginClient $logins = new LoginClient(),
        Versions $versions = new InMemoryVersions(),
    ): Client|Ending {
        $tried = $versions->current();
        $entered = self::attempt($credentials, $stop, $logins, $tried);
        $demanded = $entered instanceof Ending ? ClientVersion::demandedIn((string) $entered->reason) : null;
        if ($demanded === null || $demanded->equals($tried)) {
            return $entered;
        }

        $versions->learn($demanded);

        return self::attempt($credentials, $stop, $logins, $demanded);
    }

    public static function enterOn(
        Connection $connection,
        PlayerName $player,
        #[SensitiveParameter]
        string $token,
        CancellationTokenInterface $stop = new NullCancellationToken(),
        ClientVersion $version = new ClientVersion(self::VERSION),
    ): Client|Ending {
        $entered = Client::enter($connection, new GameLogin($player, $token, $version), $stop);
        if ($entered instanceof Client) {
            return $entered;
        }

        if ($entered instanceof Disconnected) {
            return new Ending(EndingStatus::Disconnected, $entered->reason);
        }

        $connection->close();

        return $entered instanceof Stopped
            ? new Ending(EndingStatus::Stopped)
            : new Ending(EndingStatus::Failed, Outcome::of($entered)->summary);
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

    private static function attempt(
        Credentials $credentials,
        CancellationTokenInterface $stop,
        LoginClient $logins,
        ClientVersion $version,
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
}
