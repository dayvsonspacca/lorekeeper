<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Support;

use InvalidArgumentException;

/**
 * @template T of NonNegativeInteger
 */
final readonly class Gauge
{
    /**
     * @param T $current
     * @param T $maximum
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public NonNegativeInteger $current,
        public NonNegativeInteger $maximum,
    ) {
        if ($current->value > $maximum->value) {
            throw new InvalidArgumentException("Gauge current {$current} is above maximum {$maximum}.");
        }
    }

    public function empty(): bool
    {
        return $this->current->value === 0;
    }

    public function full(): bool
    {
        return $this->current->value === $this->maximum->value;
    }
}
