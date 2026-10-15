<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Psl\Type;
use Stringable;

final readonly class PlayerLevel implements Stringable
{
    /** @var int<1, 100> */
    public int $value;

    public function __construct(int $value)
    {
        $this->value = Type\int_range(min: 1, max: 100)->assert($value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
