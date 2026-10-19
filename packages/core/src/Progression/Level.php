<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Progression;

use Psl\Type;
use Stringable;

abstract readonly class Level implements Stringable
{
    /** @var positive-int */
    public int $value;

    final public function __construct(int $value)
    {
        $this->value = Type\int_range(min: 1, max: static::maximum())->assert($value);
    }

    /** @return positive-int */
    abstract protected static function maximum(): int;

    final public function __toString(): string
    {
        return (string) $this->value;
    }
}
