<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

use InvalidArgumentException;
use Stringable;

final readonly class DropRate implements Stringable
{
    public float $percentage;

    public function __construct(float $percentage)
    {
        if ($percentage < 0.0 || $percentage > 100.0) {
            throw new InvalidArgumentException("Drop rate must be between 0 and 100, got '{$percentage}'.");
        }

        $this->percentage = $percentage;
    }

    public function __toString(): string
    {
        return "{$this->percentage}%";
    }
}
