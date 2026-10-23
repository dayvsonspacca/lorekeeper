<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Level;

use InvalidArgumentException;
use Psl\Type;

final readonly class LevelRange
{
    /** @var int<0, 100> */
    public int $minimum;

    /** @var int<0, 100> */
    public int $maximum;

    public function __construct(int $minimum, int $maximum)
    {
        $this->minimum = Type\int_range(min: 0, max: 100)->assert($minimum);
        $this->maximum = Type\int_range(min: 0, max: 100)->assert($maximum);

        if ($this->minimum > $this->maximum) {
            throw new InvalidArgumentException("Level range minimum {$minimum} is above maximum {$maximum}.");
        }
    }
}
