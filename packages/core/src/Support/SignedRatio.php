<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use InvalidArgumentException;
use Stringable;

abstract readonly class SignedRatio implements Stringable
{
    public float $value;

    /** @throws InvalidArgumentException */
    final public function __construct(float $value)
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException("Ratio {$value} is not a finite number.");
        }

        $this->value = $value;
    }

    final public function __toString(): string
    {
        return (string) $this->value;
    }
}
