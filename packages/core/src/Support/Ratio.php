<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use InvalidArgumentException;
use Stringable;

abstract readonly class Ratio implements Stringable
{
    public float $value;

    /** @throws InvalidArgumentException */
    final public function __construct(float $value)
    {
        if (!is_finite($value) || $value < 0.0) {
            throw new InvalidArgumentException("Ratio {$value} is not a finite number of zero or more.");
        }

        $this->value = $value;
    }

    final public function __toString(): string
    {
        return (string) $this->value;
    }
}
