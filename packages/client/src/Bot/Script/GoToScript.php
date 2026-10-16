<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Generator;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Game\JoinMapScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Override;
use Psl\Option;

/** @implements Script<Position|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
final readonly class GoToScript implements Script
{
    public function __construct(
        private PlayerName $player,
        private Position $here,
        private MapName $map,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, Position|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
    #[Override]
    public function run(): Generator
    {
        if ($this->here->map->value === $this->map->value) {
            return $this->here;
        }

        $joining = new JoinMapScript($this->player, $this->map, Option\none())->run();
        yield from $joining;
        $joined = $joining->getReturn();

        return match (true) {
            $joined instanceof MovedToMapEvent => new Position($joined->map, $joined->areaId),
            $joined instanceof AlreadyInMapEvent => $this->here,
            default => $joined,
        };
    }
}
