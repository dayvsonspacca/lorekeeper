<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use Psl\Type;
use Stringable;

abstract readonly class PositiveInteger implements Stringable
{
    /** @var positive-int */
    public int $value;

    final public function __construct(int $value)
    {
        $this->value = Type\positive_int()->assert($value);
    }

    final public function __toString(): string
    {
        return (string) $this->value;
    }
}
