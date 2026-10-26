<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\StatsTracking;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\Evasion;
use Lorekeeper\Core\Game\Attribute\Stat\Haste;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Strength;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Stats;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Player\Hero\HeroStatsUpdatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatsTrackingTest extends TestCase
{
    #[Test]
    public function an_update_changes_only_the_stats_it_carries(): void
    {
        $stats = new Stats();
        $hit = $stats->chances->hit;

        $changed = StatsTracking::follow($stats, new HeroStatsUpdatedEvent([new Evasion(0.7878), new Haste(0.5509)]));

        static::assertTrue($changed);
        static::assertSame(0.7878, $stats->chances->evasion->value);
        static::assertSame(0.5509, $stats->haste->value);
        static::assertSame($hit, $stats->chances->hit);
    }

    #[Test]
    public function every_family_takes_its_own_stat(): void
    {
        $stats = new Stats();

        StatsTracking::follow($stats, new HeroStatsUpdatedEvent([new Strength(245), new DamageResistance(1.2)]));

        static::assertSame(245, $stats->primary->strength->value);
        static::assertSame(0, $stats->primary->luck->value);
        static::assertSame(1.2, $stats->resistances->damage->value);
        static::assertSame(0.0, $stats->resistances->magical->value);
    }

    #[Test]
    public function other_events_leave_the_stats_as_they_were(): void
    {
        static::assertFalse(StatsTracking::follow(new Stats(), new ResurrectedEvent()));
    }
}
