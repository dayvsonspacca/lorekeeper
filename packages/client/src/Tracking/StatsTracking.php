<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Attribute\Stat\Boost\Boost;
use Lorekeeper\Core\Attribute\Stat\Boost\Boosts;
use Lorekeeper\Core\Attribute\Stat\Boost\DamageBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\DotBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\HealingBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\MagicalBoost;
use Lorekeeper\Core\Attribute\Stat\Boost\PhysicalBoost;
use Lorekeeper\Core\Attribute\Stat\Chance\Chance;
use Lorekeeper\Core\Attribute\Stat\Chance\Chances;
use Lorekeeper\Core\Attribute\Stat\Chance\CriticalChance;
use Lorekeeper\Core\Attribute\Stat\Chance\Evasion;
use Lorekeeper\Core\Attribute\Stat\Chance\HitChance;
use Lorekeeper\Core\Attribute\Stat\CriticalMultiplier;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Stat\HealingIntake;
use Lorekeeper\Core\Attribute\Stat\Power\AttackPower;
use Lorekeeper\Core\Attribute\Stat\Power\Power;
use Lorekeeper\Core\Attribute\Stat\Power\Powers;
use Lorekeeper\Core\Attribute\Stat\Power\SpellPower;
use Lorekeeper\Core\Attribute\Stat\Primary\Dexterity;
use Lorekeeper\Core\Attribute\Stat\Primary\Endurance;
use Lorekeeper\Core\Attribute\Stat\Primary\Intellect;
use Lorekeeper\Core\Attribute\Stat\Primary\Luck;
use Lorekeeper\Core\Attribute\Stat\Primary\PrimaryAttribute;
use Lorekeeper\Core\Attribute\Stat\Primary\PrimaryAttributes;
use Lorekeeper\Core\Attribute\Stat\Primary\Strength;
use Lorekeeper\Core\Attribute\Stat\Primary\Wisdom;
use Lorekeeper\Core\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\DotResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\MagicalResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\PhysicalResistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\Resistance;
use Lorekeeper\Core\Attribute\Stat\Resistance\Resistances;
use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Attribute\Stat\Stats;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\StatsUpdatedEvent;

/** @mago-expect lint:cyclomatic-complexity */
final readonly class StatsTracking
{
    public static function follow(Stats $stats, Event $event): Stats
    {
        if (!$event instanceof StatsUpdatedEvent) {
            return $stats;
        }

        foreach ($event->stats as $stat) {
            $stats = self::with($stats, $stat);
        }

        return $stats;
    }

    /** @mago-expect lint:halstead */
    private static function with(Stats $stats, Stat $stat): Stats
    {
        return new Stats(
            $stat instanceof PrimaryAttribute ? self::primary($stats->primary, $stat) : $stats->primary,
            $stat instanceof Power ? self::powers($stats->powers, $stat) : $stats->powers,
            $stat instanceof Chance ? self::chances($stats->chances, $stat) : $stats->chances,
            $stat instanceof Haste ? $stat : $stats->haste,
            $stat instanceof CriticalMultiplier ? $stat : $stats->criticalMultiplier,
            $stat instanceof Resistance ? self::resistances($stats->resistances, $stat) : $stats->resistances,
            $stat instanceof Boost ? self::boosts($stats->boosts, $stat) : $stats->boosts,
            $stat instanceof HealingIntake ? $stat : $stats->healingIntake,
        );
    }

    private static function primary(PrimaryAttributes $primary, PrimaryAttribute $stat): PrimaryAttributes
    {
        return new PrimaryAttributes(
            $stat instanceof Strength ? $stat : $primary->strength,
            $stat instanceof Intellect ? $stat : $primary->intellect,
            $stat instanceof Dexterity ? $stat : $primary->dexterity,
            $stat instanceof Endurance ? $stat : $primary->endurance,
            $stat instanceof Wisdom ? $stat : $primary->wisdom,
            $stat instanceof Luck ? $stat : $primary->luck,
        );
    }

    private static function powers(Powers $powers, Power $stat): Powers
    {
        return new Powers(
            $stat instanceof AttackPower ? $stat : $powers->attack,
            $stat instanceof SpellPower ? $stat : $powers->spell,
        );
    }

    private static function chances(Chances $chances, Chance $stat): Chances
    {
        return new Chances(
            $stat instanceof HitChance ? $stat : $chances->hit,
            $stat instanceof CriticalChance ? $stat : $chances->critical,
            $stat instanceof Evasion ? $stat : $chances->evasion,
        );
    }

    private static function resistances(Resistances $resistances, Resistance $stat): Resistances
    {
        return new Resistances(
            $stat instanceof DamageResistance ? $stat : $resistances->damage,
            $stat instanceof PhysicalResistance ? $stat : $resistances->physical,
            $stat instanceof MagicalResistance ? $stat : $resistances->magical,
            $stat instanceof DotResistance ? $stat : $resistances->dot,
        );
    }

    private static function boosts(Boosts $boosts, Boost $stat): Boosts
    {
        return new Boosts(
            $stat instanceof DamageBoost ? $stat : $boosts->damage,
            $stat instanceof PhysicalBoost ? $stat : $boosts->physical,
            $stat instanceof MagicalBoost ? $stat : $boosts->magical,
            $stat instanceof HealingBoost ? $stat : $boosts->healing,
            $stat instanceof DotBoost ? $stat : $boosts->dot,
        );
    }
}
