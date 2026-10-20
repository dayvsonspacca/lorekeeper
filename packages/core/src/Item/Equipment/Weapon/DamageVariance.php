<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\Weapon;

use Psl\Type;
use Stringable;

final readonly class DamageVariance implements Stringable
{
    /** @var int<0, 100> */
    public int $percent;

    public function __construct(int $percent)
    {
        $this->percent = Type\int_range(min: 0, max: 100)->assert($percent);
    }

    public function __toString(): string
    {
        return "{$this->percent}%";
    }
}
