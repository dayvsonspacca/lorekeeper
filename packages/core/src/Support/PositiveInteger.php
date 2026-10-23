<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use Psl\Comparison\Equable;
use Psl\Type;
use Stringable;

/**
 * @implements Equable<static>
 */
abstract readonly class PositiveInteger implements Equable, Stringable
{
    /** @var positive-int */
    public int $value;

    final public function __construct(int $value)
    {
        $this->value = Type\positive_int()->assert($value);
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
