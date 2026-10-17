<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Lorekeeper\Server\RoomId;
use Override;
use Psl\DateTime\Duration;
use Psl\Option\Option;

/** @implements Script<Event|TimedOut> */
final readonly class JoinMapScript implements Script
{
    /** @param Option<RoomId> $room */
    public function __construct(
        private PlayerName $player,
        private MapName $map,
        private Option $room,
    ) {}

    /**
     * @return Generator<int, Send, null|Event|TimedOut, Event|TimedOut>
     *
     * @mago-expect lint:inline-variable-return
     */
    #[Override]
    public function run(): Generator
    {
        /** @var Event|TimedOut $answer */
        $answer = yield new Send(new JoinMapCommand($this->player, $this->map, $this->room))->awaits(
            Duration::seconds(15),
            MapJoins::class,
            MapRefusals::class,
        );

        return $answer;
    }
}
