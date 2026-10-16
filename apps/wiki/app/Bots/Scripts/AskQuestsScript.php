<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots\Scripts;

use Generator;
use Lorekeeper\Apps\Wiki\Bots\Position;
use Lorekeeper\Client\Script\Game\LoadQuestsScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<Done|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
final readonly class AskQuestsScript implements Script
{
    /** @param non-empty-list<QuestId> $quests */
    public function __construct(
        private PlayerName $player,
        private Position $here,
        private MapName $map,
        private array $quests,
    ) {}

    /** @return Generator<int, Instruction, null|Event|TimedOut, Done|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent> */
    #[Override]
    public function run(): Generator
    {
        $there = yield from new GoToScript($this->player, $this->here, $this->map)->run();
        if (!$there instanceof Position) {
            return $there;
        }

        $answers = yield from new LoadQuestsScript($there->area, $this->quests, Duration::seconds(2))->run();

        $loaded = 0;
        foreach ($answers as $answer) {
            $loaded += $answer instanceof QuestLoadedEvent ? count($answer->quests) : 0;
        }

        return new Done("Loaded {$loaded} of " . count($this->quests) . ' quests');
    }
}
