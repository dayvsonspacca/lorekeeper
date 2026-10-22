<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Quests;

use Generator;
use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Core\World\Quest\QuestId;
use Override;

final readonly class AcceptQuest implements Action
{
    public function __construct(
        public QuestId $quest,
    ) {}

    #[Override]
    public function describe(): string
    {
        return "Accept quest {$this->quest->value}";
    }

    #[Override]
    public function play(StepScript $bot): Generator
    {
        $accepting = $bot->accept($this->quest);
        yield from $accepting;

        return $accepting->getReturn() ? null : new Failed('the game would not give the quest');
    }
}
