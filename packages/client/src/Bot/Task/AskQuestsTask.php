<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Task;

use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Script\AskQuestsScript;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Override;

final readonly class AskQuestsTask implements Task
{
    /** @param non-empty-list<QuestId> $quests */
    public function __construct(
        public MapName $map,
        public array $quests,
    ) {}

    #[Override]
    public function title(): string
    {
        return 'Ask for ' . count($this->quests) . " quests in {$this->map->value}";
    }

    #[Override]
    public function script(Character $me): Script
    {
        return new AskQuestsScript($me->name(), $me->position(), $this->map, $this->quests);
    }
}
