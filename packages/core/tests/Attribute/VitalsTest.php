<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Attribute;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
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

    #[Test]
    public function it_changes_health_and_mana_without_touching_their_maximums(): void
    {
        $vitals = self::vitals(health: 390)->withHealth(new Health(189))->withMana(new Mana(87));

        static::assertSame([189, 390, 87, 100], [
            $vitals->health->value,
            $vitals->maxHealth->value,
            $vitals->mana->value,
            $vitals->maxMana->value,
        ]);
    }

    private static function vitals(int $health): Vitals
    {
        return new Vitals(new Health($health), new Health(390), new Mana(100), new Mana(100));
    }
}
