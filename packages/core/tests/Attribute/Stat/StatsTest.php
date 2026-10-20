<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Attribute\Stat;

use InvalidArgumentException;
use Lorekeeper\Core\Attribute\Stat\Boost\MagicalBoost;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\DotResistance;
use Lorekeeper\Core\Attribute\Stat\Stats;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatsTest extends TestCase
{
    #[Test]
    public function a_hero_starts_from_the_games_base_values(): void
    {
        $stats = new Stats();

        static::assertSame(0.9, $stats->chances->hit->value);
        static::assertSame(0.05, $stats->chances->critical->value);
        static::assertSame(1.5, $stats->criticalMultiplier->value);
        static::assertSame(0.04, $stats->chances->evasion->value);
        static::assertSame(0.0, $stats->haste->value);
        static::assertSame(1.0, $stats->healingIntake->value);
    }

    #[Test]
    public function haste_counts_up_to_half_and_resistance_up_to_four_fifths(): void
    {
        static::assertSame(0.25, new Haste(0.25)->effective());
        static::assertSame(0.5, new Haste(0.731)->effective());
        static::assertSame(0.8, new DamageResistance(0.95)->effective());
        static::assertSame(0.95, new DotResistance(0.95)->effective());
    }

    #[Test]
    public function each_stat_describes_itself_as_the_game_does(): void
    {
        static::assertStringContainsString('hard capped at 50%', new Haste(0.1)->describe()->value);
        static::assertSame(
            'Reduces all incoming damage, except True Damage.',
            new DamageResistance(0.1)->describe()->value,
        );
    }

    #[Test]
    public function a_chance_or_haste_below_zero_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Haste(-0.1);
    }

    #[Test]
    public function a_resistance_or_boost_can_fall_below_normal(): void
    {
        static::assertSame(-0.6, new DamageResistance(-0.6)->effective());
        static::assertSame(-0.4389, new MagicalBoost(-0.4389)->value);
    }
}
