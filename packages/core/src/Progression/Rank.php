<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Progression;

use Psl\Type;
use Stringable;

final readonly class Rank implements Stringable
{
    private const array MINIMUM_POINTS = [
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

    public static function fromPoints(ClassPoints|Reputation $points): self
    {
        $rank = 1;

        foreach (self::MINIMUM_POINTS as $candidate => $minimum) {
            if ($points->value < $minimum) {
                break;
            }

            $rank = $candidate;
        }

        return new self($rank);
    }

    public function minimumPoints(): int
    {
        return self::MINIMUM_POINTS[$this->value];
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
