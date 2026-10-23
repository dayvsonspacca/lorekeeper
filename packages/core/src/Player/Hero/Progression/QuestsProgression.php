<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Progression;

use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;

final class QuestsProgression
{
    /** @param list<QuestGroupId> $completed */
    public function __construct(
        public private(set) array $completed = [],
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

    public function complete(QuestGroupId $id): void
    {
        if ($this->has($id)) {
            return;
        }

        $this->completed[] = $id;
    }

    public function forget(QuestGroupId $id): void
    {
        $this->completed = array_values(array_filter(
            $this->completed,
            static fn(QuestGroupId $done): bool => !$done->equals($id),
        ));
    }

    public function reset(QuestGroup $group): void
    {
        $this->completed = array_values(array_filter(
            $this->completed,
            static fn(QuestGroupId $done): bool => $done->group !== $group,
        ));
    }
}
