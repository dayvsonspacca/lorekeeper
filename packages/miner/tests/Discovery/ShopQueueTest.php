<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use DateTimeImmutable;
use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Core\World\Shop\ShopUnavailable;
use Lorekeeper\Miner\Discovery\ShopQueue;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class ShopQueueTest extends TestCase
{
    #[Test]
    public function it_groups_unknown_shops_by_map_once(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15'))->record([
            new Fact(new MapName('nulgath'), new MemberOnly()),
            new Fact(self::npc('battleon', 'Twilly'), new NpcShop(new ShopId(31))),
            new Fact(self::npc('battleon', 'Twilly'), new NpcShop(new ShopId(314))),
            new Fact(self::npc('battleon', 'Valencia'), new NpcShop(new ShopId(314))),
            new Fact(self::npc('battleon', 'Ragnar'), new NpcShop(new ShopId(299))),
            new Fact(self::npc('nulgath', 'Nulgath'), new NpcShop(new ShopId(800))),
            new Fact(self::npc('yulgar', 'Yulgar'), new NpcShop(new ShopId(31))),
            new Fact(new ShopId(299), new ShopName('Class Shop')),
        ], Source::Swf);

        static::assertEquals(['battleon' => [new ShopId(31), new ShopId(314)]], self::queue($store)->toLoad());
    }

    #[Test]
    public function it_waits_before_retrying_shops_that_did_not_answer(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $recorder = static fn(string $at): Recorder => new Recorder(
            $store,
            static fn(): DateTimeImmutable => new DateTimeImmutable($at),
        );
        $recorder('2026-10-01')->record([new Fact(new ShopId(87), new ShopUnavailable())], Source::Socket);
        $recorder('2026-10-14')->record([new Fact(new ShopId(100), new ShopUnavailable())], Source::Socket);
        $recorder('2026-10-01')
            ->record([
                new Fact(self::npc('shadowfallwar', 'Gravelyn'), new NpcShop(new ShopId(87))),
                new Fact(self::npc('shadowfallwar', 'Doomy'), new NpcShop(new ShopId(100))),
            ], Source::Swf);

        static::assertEquals(['shadowfallwar' => [new ShopId(87)]], self::queue($store)->toLoad());
    }

    private static function queue(SqliteFactStore $store): ShopQueue
    {
        return new ShopQueue(
            $store,
            static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15'),
            Duration::hours(7 * 24),
        );
    }

    private static function npc(string $map, string $name): NpcId
    {
        return new NpcId(new MapName($map), new Linkage("{$name}_APOP"));
    }
}
