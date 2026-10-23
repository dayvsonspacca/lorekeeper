<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use Psl\Comparison\Equable;
use Psl\Type;
use Stringable;

/**
 * @implements Equable<static>
 */
abstract readonly class NonNegativeInteger implements Equable, Stringable
{
    /** @var non-negative-int */
    public int $value;

    final public function __construct(int $value)
    {
        $this->value = Type\uint()->assert($value);
    }

    /**
     * @param static $other
     *
     * @mago-expect analysis:redundant-logical-operation
     */
    final public function equals(mixed $other): bool
    {
        return $other instanceof static && $other->value === $this->value;
    }

    final public function __toString(): string
    {
        return (string) $this->value;
    }
}
