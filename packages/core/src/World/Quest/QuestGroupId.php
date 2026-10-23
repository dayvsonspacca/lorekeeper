<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

use Psl\Comparison\Equable;

/**
 * @implements Equable<QuestGroupId>
 */
final readonly class QuestGroupId implements Equable
{
    public function __construct(
        public QuestGroup $group,
        public QuestGroupNumber $number,
    ) {}

    /**
     * @param QuestGroupId $other
     *
     * @mago-expect analysis:redundant-logical-operation
     */
    public function equals(mixed $other): bool
    {
        return $other instanceof self && $other->group === $this->group && $other->number->equals($this->number);
    }
}
