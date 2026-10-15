<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Psl\Type;
use Stringable;

final readonly class MonsterLevel implements Stringable
{
    /** @var int<1, 255> */
    public int $value;

    public function __construct(int $value)
    {
        $this->value = Type\int_range(min: 1, max: 255)->assert($value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
