<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\World\Quest\QuestMark;

final readonly class PlayerQuestMarks
{
    /** @param list<QuestMark> $marks */
    public function __construct(
        public array $marks = [],
    ) {}

    public function has(QuestMark $mark): bool
    {
        foreach ($this->marks as $held) {
            if ($held->is($mark)) {
                return true;
            }
        }

        return false;
    }
}
