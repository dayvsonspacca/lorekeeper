<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Core\Game\Attribute\Stat\Boost\DamageBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\MagicalBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\Evasion;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\HitChance;
use Lorekeeper\Core\Game\Attribute\Stat\CriticalMultiplier;
use Lorekeeper\Core\Game\Attribute\Stat\Haste;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Luck;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Strength;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\MagicalResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Stat;
use Lorekeeper\Server\Event\Player\StatsUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatsUpdatedEventTest extends TestCase
{
    private const string ALL_OF_THEM = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"stu","sta":{"_STR":183,"$STR":245,"_END":152,"$END":214,"_DEX":152,"$DEX":214,"_INT":183,"$INT":245,"_WIS":53,"$WIS":115,"_LCK":38,"$LCK":348,"$dsh":50,"$ap":734,"$sp":734,"$tdo":0.2639,"$tcr":0.3828,"$thi":0.2185,"$tha":0.219,"$cpo":1,"$cpi":1,"$cao":1,"$cai":1,"$cmo":0.5611,"$cmi":0.6889,"$cdo":1,"$cdi":1,"$cho":1,"$chi":1,"$cmc":1,"$scm":4.0095,"$shb":0,"$smb":0}}}}
        JSON;

    #[Test]
    public function it_reads_every_stat_as_the_stats_panel_shows_it(): void
    {
        $stats = self::read(self::ALL_OF_THEM);

        static::assertCount(23, $stats);
        static::assertSame(
            '23 stats updated',
            StatsUpdatedEvent::from(JsonMessage::from(self::ALL_OF_THEM)->unwrap())->unwrap()->summary(),
        );
        static::assertSame(245, self::of($stats, Strength::class));
        static::assertSame(348, self::of($stats, Luck::class));
        static::assertEqualsWithDelta(1.1185, self::of($stats, HitChance::class), 1e-9);
        static::assertEqualsWithDelta(0.219, self::of($stats, Haste::class), 1e-9);
        static::assertEqualsWithDelta(4.0095, self::of($stats, CriticalMultiplier::class), 1e-9);
        static::assertEqualsWithDelta(0.0, self::of($stats, DamageResistance::class), 1e-9);
        static::assertEqualsWithDelta(0.3111, self::of($stats, MagicalResistance::class), 1e-9);
        static::assertEqualsWithDelta(-0.4389, self::of($stats, MagicalBoost::class), 1e-9);
    }

    #[Test]
    public function an_aura_sends_only_the_stats_it_moved(): void
    {
        $stats = self::read(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"stu","sta":{"$tdo":0.7878000000000001,"$tha":0.5509}}}}',
        );

        static::assertCount(2, $stats);
        static::assertEqualsWithDelta(0.7878, self::of($stats, Evasion::class), 1e-9);
        static::assertEqualsWithDelta(0.5509, self::of($stats, Haste::class), 1e-9);
    }

    #[Test]
    public function a_resistance_can_go_past_all_damage(): void
    {
        $stats = self::read('{"t":"xt","b":{"r":-1,"o":{"cmd":"stu","sta":{"$cao":1.5,"$cai":-0.2,"$scm":4.5103}}}}');

        static::assertEqualsWithDelta(1.2, self::of($stats, DamageResistance::class), 1e-9);
        static::assertEqualsWithDelta(0.5, self::of($stats, DamageBoost::class), 1e-9);
    }

    #[Test]
    public function it_ignores_an_update_with_no_stat_it_knows(): void
    {
        static::assertTrue(
            StatsUpdatedEvent::from(
                JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"stu","sta":{"$cmc":1}}}}')->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function it_refuses_a_stat_out_of_range(): void
    {
        $this->expectException(MalformedEventException::class);

        StatsUpdatedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"stu","sta":{"$tha":-0.1}}}}')->unwrap(),
        );
    }

    /** @return non-empty-list<Stat> */
    private static function read(string $json): array
    {
        return StatsUpdatedEvent::from(JsonMessage::from($json)->unwrap())->unwrap()->stats;
    }

    /**
     * @param list<Stat> $stats
     * @param class-string<Stat> $class
     */
    private static function of(array $stats, string $class): int|float
    {
        foreach ($stats as $stat) {
            if ($stat instanceof $class && property_exists($stat, 'value')) {
                return $stat->value;
            }
        }

        static::fail("no {$class} was read");
    }
}
