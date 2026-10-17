<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Generator;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapJoins;
use Lorekeeper\Server\Event\Map\MapRefusals;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Override;
use Psl\DateTime\Duration;
use Psl\Option;

/** @implements Script<Position|TimedOut|Event> */
final readonly class GoToScript implements Script
{
    public function __construct(
        private PlayerName $player,
        private Position $here,
        private MapName $map,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, Position|TimedOut|Event> */
    #[Override]
    public function run(): Generator
    {
        if ($this->here->map->value === $this->map->value) {
            return $this->here;
        }

        /** @var Event|TimedOut $joined */
        $joined = yield new Send(new JoinMapCommand($this->player, $this->map, Option\none()))->awaits(
            Duration::seconds(15),
            MapJoins::class,
            MapRefusals::class,
        );

        return match (true) {
            $joined instanceof MovedToMapEvent => new Position($joined->map, $joined->areaId),
            MapJoins::contains($joined) => $this->here,
            default => $joined,
        };
    }
}
