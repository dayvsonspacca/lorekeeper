<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

use InvalidArgumentException;

final readonly class QuestMark
{
    /** @throws InvalidArgumentException */
    public function __construct(
        public QuestPeriod $period,
        public int $group,
        public int $index,
    ) {
        if ($group < 0 || $index < 0) {
            throw new InvalidArgumentException("A quest mark has no place at {$group}:{$index}.");
        }
    }

    public function is(self $other): bool
    {
        return $this->period === $other->period && $this->group === $other->group && $this->index === $other->index;
    }
}
