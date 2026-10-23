<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Rank;

use Lorekeeper\Core\Game\Rank\Traits\GrantsRank;
use Psl\Type;
use Stringable;

final readonly class Rank implements Stringable
{
    private const array STARTS = [
        1 => 0,
        2 => 900,
        3 => 3600,
        4 => 10_000,
        5 => 22_500,
        6 => 44_100,
        7 => 78_400,
        8 => 129_600,
        9 => 202_500,
        10 => 302_500,
    ];

    /** @var int<1, 10> */
    public int $value;

    public function __construct(int $value)
    {
        $this->value = Type\int_range(min: 1, max: 10)->assert($value);
    }

    public static function of(GrantsRank $points): self
    {
        $rank = 1;
        foreach (self::STARTS as $candidate => $start) {
            if ($points->value >= $start) {
                $rank = $candidate;
            }
        }

        return new self($rank);
    }

    public function start(): int
    {
        return self::STARTS[$this->value];
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
