<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder;

use Closure;
use Lorekeeper\Client\Api\Login\LoginClient;
use Lorekeeper\Client\Api\Server\ServerChoice;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\Outcome;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Session\Credentials;
use Lorekeeper\Client\Session\Login;
use Psl\Async\CancellationTokenInterface;
use RuntimeException;
use SensitiveParameter;

final readonly class Session
{
    public function __construct(
        private LoginClient $logins = new LoginClient(),
    ) {}

    /**
     * @param Script<mixed> $script
     * @param Closure(string): Recording $recording
     *
     * @throws RuntimeException
     */
    public function record(
        #[SensitiveParameter]
        Credentials $credentials,
        Script $script,
        Closure $recording,
        CancellationTokenInterface $stop,
    ): string {
        $login = $this->logins->login($credentials->player, $credentials->password);
        $server = ServerChoice::pick($login->servers, $credentials->server)->unwrapOr(
            null,
        ) ?? throw new RuntimeException('There is no open server.');

        $connection = Connection::open($server);
        $connection->subscribe($recording($login->token));

        $entered = Login::enterOn($connection, $credentials->player, $login->token, $stop);
        if (!$entered instanceof Client) {
            return (
                "Could not enter the game: {$entered->status->name}"
                . ($entered->reason === null ? '' : " ({$entered->reason})")
            );
        }

        $outcome = Outcome::of($entered->run($script, $stop));
        Login::leave($entered);

        return "{$script->title()}: {$outcome->summary}";
    }
}
