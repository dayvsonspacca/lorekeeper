<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\StatsTracking;
use Lorekeeper\Core\Attribute\Stat\Chance\Evasion;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Stat\Primary\Strength;
use Lorekeeper\Core\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Attribute\Stat\Stats;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Player\StatsUpdatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatsTrackingTest extends TestCase
{
    #[Test]
    public function an_update_changes_only_the_stats_it_carries(): void
    {
        $before = new Stats();

        $after = StatsTracking::follow($before, new StatsUpdatedEvent([new Evasion(0.7878), new Haste(0.5509)]));

        static::assertSame(0.7878, $after->chances->evasion->value);
        static::assertSame(0.5509, $after->haste->value);
        static::assertSame($before->chances->hit, $after->chances->hit);
        static::assertSame($before->resistances, $after->resistances);
        static::assertSame($before->primary, $after->primary);
    }

    #[Test]
    public function every_family_takes_its_own_stat(): void
    {
        $after = StatsTracking::follow(
            new Stats(),
            new StatsUpdatedEvent([new Strength(245), new DamageResistance(1.2)]),
        );

        static::assertSame(245, $after->primary->strength->value);
        static::assertSame(0, $after->primary->luck->value);
        static::assertSame(1.2, $after->resistances->damage->value);
        static::assertSame(0.0, $after->resistances->magical->value);
    }

    #[Test]
    public function other_events_leave_the_stats_as_they_were(): void
    {
        $stats = new Stats();

        static::assertSame($stats, StatsTracking::follow($stats, new ResurrectedEvent()));
    }
}
