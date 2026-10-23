<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Game\Attribute;

use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
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

    #[Test]
    public function it_is_hurt_down_to_nothing_and_healed_up_to_its_maximum(): void
    {
        $vitals = self::vitals(health: 300);

        $vitals->hurt(new Health(100));
        static::assertSame(200, $vitals->health->current->value);

        $vitals->heal(new Health(500));
        static::assertSame(390, $vitals->health->current->value);

        $vitals->hurt(new Health(9999));
        static::assertSame(0, $vitals->health->current->value);
    }

    #[Test]
    public function it_spends_mana_down_to_nothing_and_regains_it_up_to_its_maximum(): void
    {
        $vitals = self::vitals(health: 390, mana: 60);

        $vitals->spend(new Mana(20));
        static::assertSame(40, $vitals->mana->current->value);

        $vitals->regain(new Mana(500));
        static::assertSame(100, $vitals->mana->current->value);

        $vitals->spend(new Mana(9999));
        static::assertSame(0, $vitals->mana->current->value);
    }

    #[Test]
    public function it_comes_back_full_only_after_dying(): void
    {
        $vitals = self::vitals(health: 200, mana: 10);

        $vitals->revive();
        static::assertSame([200, 10], [$vitals->health->current->value, $vitals->mana->current->value]);

        $vitals->die();
        static::assertFalse($vitals->alive());

        $vitals->revive();
        static::assertSame([390, 100], [$vitals->health->current->value, $vitals->mana->current->value]);
    }

    #[Test]
    public function it_takes_whatever_the_server_recounts(): void
    {
        $vitals = self::vitals(health: 200);

        $vitals->recount(new Gauge(new Health(2585), new Health(2585)), new Gauge(new Mana(80), new Mana(100)));

        static::assertSame([2585, 2585], [$vitals->health->current->value, $vitals->health->maximum->value]);
        static::assertSame([80, 100], [$vitals->mana->current->value, $vitals->mana->maximum->value]);
    }

    private static function vitals(int $health, int $mana = 100): Vitals
    {
        return new Vitals(new Gauge(new Health($health), new Health(390)), new Gauge(new Mana($mana), new Mana(100)));
    }
}
