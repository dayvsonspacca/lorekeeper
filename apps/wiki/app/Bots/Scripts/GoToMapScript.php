<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

use Generator;
use Lorekeeper\Apps\Wiki\Bots\Position;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Override;

/** @implements Script<Done|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
final readonly class GoToMapScript implements Script
{
    public function __construct(
        private PlayerName $player,
        private Position $here,
        private MapName $map,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, Done|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
    #[Override]
    public function run(): Generator
    {
        $there = yield from new GoToScript($this->player, $this->here, $this->map)->run();
        if (!$there instanceof Position) {
            return $there;
        }

        return new Done($there === $this->here ? "Already in {$this->map->value}" : "Now in {$this->map->value}");
    }
}
