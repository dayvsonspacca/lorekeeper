<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Quests;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Quest\QuestId;
use Override;

final readonly class TurnInQuest implements Action
{
    public function __construct(
        public QuestId $quest,
        public ?ItemId $reward,
    ) {}

    #[Override]
    public function describe(): string
    {
        return $this->reward === null
            ? "Turn in quest {$this->quest->value}"
            : "Turn in quest {$this->quest->value} for item {$this->reward->value}";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        $heldBefore = $this->reward === null ? 0 : $bot->me()->hero()->inventory->quantity($this->reward);
        $turningIn = $bot->turnIn($this->quest);
        yield from $turningIn;
        $refusal = $turningIn->getReturn();
        if ($refusal !== null) {
            return new Failed("the game would not complete the quest ({$refusal})");
        }

        $reward = $this->reward;
        if ($reward === null) {
            return null;
        }

        $collecting = $bot->collect($reward, $heldBefore);
        yield from $collecting;

        return $collecting->getReturn()
            ? null
            : new Failed("the quest was completed, but item {$reward->value} could not be picked up");
    }
}
