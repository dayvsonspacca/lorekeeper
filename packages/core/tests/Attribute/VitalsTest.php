<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Attribute;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Support\Gauge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VitalsTest extends TestCase
{
    #[Test]
    public function it_is_alive_while_there_is_health_left(): void
    {
        static::assertTrue(self::vitals(health: 1)->alive());
        static::assertFalse(self::vitals(health: 0)->alive());
    }

    private static function vitals(int $health): Vitals
    {
        return new Vitals(new Gauge(new Health($health), new Health(390)), new Gauge(new Mana(100), new Mana(100)));
    }
}
