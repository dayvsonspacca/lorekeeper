<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use Psl\Comparison\Equable;
use Psl\Type;
use Stringable;

/**
 * @implements Equable<static>
 */
abstract readonly class NonEmptyString implements Equable, Stringable
{
    /** @var non-empty-string */
    public string $value;

    final public function __construct(string $value)
    {
        $this->value = Type\non_empty_string()->assert($value);
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
        return $this->value;
    }
}
