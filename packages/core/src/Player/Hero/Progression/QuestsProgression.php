<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Progression;

use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestGroupId;

final readonly class QuestsProgression
{
    /** @param list<QuestGroupId> $completed */
    public function __construct(
        public array $completed = [],
    ) {}

    public function completed(Quest $quest): bool
    {
        return $quest->groupId->map($this->has(...))->unwrapOr(false);
    }

    public function has(QuestGroupId $id): bool
    {
        foreach ($this->completed as $done) {
            if ($done->equals($id)) {
                return true;
            }
        }

        return false;
    }
}
