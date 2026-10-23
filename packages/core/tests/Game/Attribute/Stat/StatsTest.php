<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Game\Attribute\Stat;

use InvalidArgumentException;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\DamageBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\DotBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\HealingBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\MagicalBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\PhysicalBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\CriticalChance;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\Evasion;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\HitChance;
use Lorekeeper\Core\Game\Attribute\Stat\CriticalMultiplier;
use Lorekeeper\Core\Game\Attribute\Stat\Haste;
use Lorekeeper\Core\Game\Attribute\Stat\HealingIntake;
use Lorekeeper\Core\Game\Attribute\Stat\Power\AttackPower;
use Lorekeeper\Core\Game\Attribute\Stat\Power\SpellPower;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Dexterity;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Endurance;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Intellect;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Luck;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Strength;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Wisdom;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\DotResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\MagicalResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\PhysicalResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Stat;
use Lorekeeper\Core\Game\Attribute\Stat\Stats;
use PHPUnit\Framework\Attributes\DataProvider;
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
    #[DataProvider('described')]
    public function every_stat_has_a_description(Stat $stat, string $fragment): void
    {
        static::assertStringContainsString($fragment, $stat->describe()->value);
    }

    /** @return iterable<string, array{Stat, string}> */
    public static function described(): iterable
    {
        yield 'damage boost' => [new DamageBoost(0.1), 'outgoing damage, except True Damage'];
        yield 'dot boost' => [new DotBoost(0.1), 'damage over time'];
        yield 'healing boost' => [new HealingBoost(0.1), 'healing given to other players'];
        yield 'magical boost' => [new MagicalBoost(0.1), 'outgoing magical damage'];
        yield 'physical boost' => [new PhysicalBoost(0.1), 'outgoing physical and hybrid damage'];
        yield 'critical chance' => [new CriticalChance(0.05), 'Critical Strike chance'];
        yield 'evasion' => [new Evasion(0.04), 'avoid incoming damage'];
        yield 'hit chance' => [new HitChance(0.9), 'Hit chance'];
        yield 'critical multiplier' => [new CriticalMultiplier(1.5), 'critical hit'];
        yield 'healing intake' => [new HealingIntake(1.0), 'healing received'];
        yield 'attack power' => [new AttackPower(10), 'physical damage attacks'];
        yield 'spell power' => [new SpellPower(10), 'magical damage attacks'];
        yield 'dexterity' => [new Dexterity(10), 'Dexterity is valuable to melee classes'];
        yield 'endurance' => [new Endurance(10), 'total Hit Points'];
        yield 'intellect' => [new Intellect(10), 'Intellect increases Spell Power'];
        yield 'luck' => [new Luck(10), 'Critical Strike modifier'];
        yield 'strength' => [new Strength(10), 'Strength increases Attack Power'];
        yield 'wisdom' => [new Wisdom(10), 'Wisdom is valuable to caster classes'];
        yield 'dot resistance' => [new DotResistance(0.1), 'incoming damage over time'];
        yield 'magical resistance' => [new MagicalResistance(0.1), 'incoming magical damage'];
        yield 'physical resistance' => [new PhysicalResistance(0.1), 'incoming physical and hybrid damage'];
    }

    #[Test]
    public function a_chance_or_haste_below_zero_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Haste(-0.1);
    }

    #[Test]
    public function a_resistance_or_boost_that_is_not_finite_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DamageBoost(INF);
    }

    #[Test]
    public function a_ratio_reads_as_its_value(): void
    {
        static::assertSame('0.25', (string) new Haste(0.25));
        static::assertSame('-0.6', (string) new DamageResistance(-0.6));
    }

    #[Test]
    public function a_resistance_or_boost_can_fall_below_normal(): void
    {
        static::assertSame(-0.6, new DamageResistance(-0.6)->effective());
        static::assertSame(-0.4389, new MagicalBoost(-0.4389)->value);
    }
}
