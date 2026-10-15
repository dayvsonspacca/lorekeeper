<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use DateTimeImmutable;
use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Miner\Discovery\ShopQueue;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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

        static::assertEquals(['battleon' => [new ShopId(31), new ShopId(314)]], new ShopQueue($store)->toLoad());
    }

    private static function npc(string $map, string $name): NpcId
    {
        return new NpcId(new MapName($map), new NpcName($name));
    }
}
