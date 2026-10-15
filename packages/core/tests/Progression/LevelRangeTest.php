<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Progression;

use InvalidArgumentException;
use Lorekeeper\Core\Progression\LevelRange;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class LevelRangeTest extends TestCase
{
    #[Test]
    #[TestWith([0, 0])]
    #[TestWith([4, 5])]
    #[TestWith([45, 100])]
    public function it_accepts_valid_ranges(int $minimum, int $maximum): void
    {
        $range = new LevelRange($minimum, $maximum);

        static::assertSame($minimum, $range->minimum);
        static::assertSame($maximum, $range->maximum);
    }

    #[Test]
    #[TestWith([-1, 5])]
    #[TestWith([1, 101])]
    public function it_rejects_levels_out_of_range(int $minimum, int $maximum): void
    {
        $this->expectException(AssertException::class);

        new LevelRange($minimum, $maximum);
    }

    #[Test]
    public function it_rejects_minimum_above_maximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LevelRange(60, 45);
    }
}
