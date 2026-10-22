<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps\Checks;

use Generator;
use Lorekeeper\Bot\Steps\Check;
use Lorekeeper\Bot\Steps\StepScript;
use Lorekeeper\Core\World\Quest\QuestId;
use Override;

final readonly class QuestDone implements Check
{
    public function __construct(
        public QuestId $quest,
    ) {}

    #[Override]
    public function describe(): string
    {
        return "Quest {$this->quest->value} was already done";
    }

    #[Override]
    public function holds(StepScript $bot): Generator
    {
        $checking = $bot->alreadyDone($this->quest);
        yield from $checking;

        return $checking->getReturn();
    }
}
