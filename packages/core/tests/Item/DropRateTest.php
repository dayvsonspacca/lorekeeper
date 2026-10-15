<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item;

use InvalidArgumentException;
use Lorekeeper\Core\Item\DropRate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class DropRateTest extends TestCase
{
    #[Test]
    #[TestWith([0.0])]
    #[TestWith([0.5])]
    #[TestWith([100.0])]
    public function it_accepts_percentages_within_range(float $percentage): void
    {
        static::assertSame($percentage, new DropRate($percentage)->percentage);
    }

    #[Test]
    #[TestWith([-0.1])]
    #[TestWith([100.1])]
    public function it_rejects_percentages_out_of_range(float $percentage): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DropRate($percentage);
    }

    #[Test]
    public function it_formats_as_percentage(): void
    {
        static::assertSame('12.5%', (string) new DropRate(12.5));
    }
}
