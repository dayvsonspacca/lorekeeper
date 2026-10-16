<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\JoinMapCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\RoomId;
use Override;
use Psl\DateTime\Duration;
use Psl\Option\Option;

/** @implements Script<MovedToMapEvent|AlreadyInMapEvent|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent|TimedOut> */
final readonly class JoinMapScript implements Script
{
    /** @param Option<RoomId> $room */
    public function __construct(
        private PlayerName $player,
        private MapName $map,
        private Option $room,
    ) {}

    /** @return Generator<int, Send, MovedToMapEvent|AlreadyInMapEvent|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent|TimedOut, MovedToMapEvent|AlreadyInMapEvent|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent|TimedOut> */
    #[Override]
    public function run(): Generator
    {
        return yield new Send(
            new JoinMapCommand($this->player, $this->map, $this->room),
            new Await([
                MovedToMapEvent::class,
                AlreadyInMapEvent::class,
                MapLockedEvent::class,
                MapMemberOnlyEvent::class,
                MapNotAvailableEvent::class,
                MapRequiresQuestEvent::class,
            ], Duration::seconds(15)),
        );
    }
}
