<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Script;

use Generator;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Game\LoadQuestsScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
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

/**
 * Goes to a map if needed and asks for the given quests, ten at a time, telling how many batches are done.
 *
 * @implements Script<Done|TimedOut|MapLockedEvent|MapMemberOnlyEvent|MapNotAvailableEvent|MapRequiresQuestEvent>
 */
final readonly class AskQuestsScript implements Script
{
    private const int BATCH = 10;

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
        $going = new GoToScript($this->player, $this->here, $this->map)->run();
        yield from $going;
        $there = $going->getReturn();
        if (!$there instanceof Position) {
            return $there;
        }

        /** @var non-empty-list<non-empty-list<QuestId>> $batches */
        $batches = array_chunk($this->quests, self::BATCH);
        $total = count($batches);
        yield new Progress(0, $total);

        $loaded = 0;
        foreach ($batches as $index => $batch) {
            if ($index > 0) {
                yield new Pause(Duration::seconds(2));
            }

            $loading = new LoadQuestsScript($there->area, $batch, Duration::seconds(2))->run();
            yield from $loading;
            $answers = $loading->getReturn();
            foreach ($answers as $answer) {
                $loaded += $answer instanceof QuestLoadedEvent ? count($answer->quests) : 0;
            }

            yield new Progress($index + 1, $total);
        }

        return new Done("Loaded {$loaded} of " . count($this->quests) . ' quests');
    }
}
