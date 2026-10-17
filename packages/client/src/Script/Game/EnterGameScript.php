<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\Inventory\LoadPlayerInventoryCommand;
use Lorekeeper\Client\Command\Map\JoinInitialMapCommand;
use Lorekeeper\Client\Command\Session\LoginCommand;
use Lorekeeper\Client\Command\Session\PolicyFileRequestCommand;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Session\LoginFailedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<InGame|LoginFailedEvent|TimedOut> */
final readonly class EnterGameScript implements Script
{
    private const int TIMEOUT_SECONDS = 15;

    public function __construct(
        private GameLogin $login,
    ) {}

    /** @return Generator<int, Send, null|Event|TimedOut, InGame|LoginFailedEvent|TimedOut> */
    #[Override]
    public function run(): Generator
    {
        $policy = yield new Send(new PolicyFileRequestCommand(), self::await(ConnectionEstablishedEvent::class));
        if ($policy instanceof TimedOut) {
            return $policy;
        }

        /** @var LoginSucceededEvent|LoginFailedEvent|TimedOut $login */
        $login = yield new Send(
            new LoginCommand($this->login->player, $this->login->token, $this->login->version),
            self::await(LoginSucceededEvent::class, LoginFailedEvent::class),
        );
        if (!$login instanceof LoginSucceededEvent) {
            return $login;
        }

        /** @var MovedToMapEvent|TimedOut $map */
        $map = yield new Send(new JoinInitialMapCommand(), self::await(MovedToMapEvent::class));
        if (!$map instanceof MovedToMapEvent) {
            return $map;
        }

        $inventory = yield new Send(
            new LoadPlayerInventoryCommand($map->areaId, $login->socketId),
            self::await(PlayerInventoryLoadedEvent::class),
        );
        if ($inventory instanceof TimedOut) {
            return $inventory;
        }

        return new InGame($login->socketId, $map);
    }

    /**
     * @param class-string<Event> $event
     * @param class-string<Event> ...$more
     */
    private static function await(string $event, string ...$more): Await
    {
        return new Await([$event, ...array_values($more)], Duration::seconds(self::TIMEOUT_SECONDS));
    }
}
