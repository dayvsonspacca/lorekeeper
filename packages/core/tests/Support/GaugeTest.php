<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use InvalidArgumentException;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class GaugeTest extends TestCase
{
    #[Test]
    #[TestWith([0, 0])]
    #[TestWith([189, 390])]
    #[TestWith([390, 390])]
    public function it_accepts_up_to_its_maximum(int $current, int $maximum): void
    {
        $gauge = new Gauge(new Health($current), new Health($maximum));

        static::assertSame($current, $gauge->current->value);
        static::assertSame($maximum, $gauge->maximum->value);
    }

    #[Test]
    public function it_rejects_going_above_its_maximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Gauge(new Health(391), new Health(390));
    }

    #[Test]
    public function it_is_empty_at_zero(): void
    {
        static::assertTrue(new Gauge(new Health(0), new Health(390))->empty());
        static::assertFalse(new Gauge(new Health(189), new Health(390))->empty());
    }

    #[Test]
    public function it_is_full_at_its_maximum(): void
    {
        static::assertTrue(new Gauge(new Health(390), new Health(390))->full());
        static::assertFalse(new Gauge(new Health(189), new Health(390))->full());
    }
}
