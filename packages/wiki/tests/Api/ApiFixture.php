<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Tests\Api;

use DateTimeImmutable;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Item\DropRate;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopItem;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Observation;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use Lorekeeper\Wiki\Api\Application;
use PDO;
use PHPUnit\Framework\TestCase;

final readonly class ApiFixture
{
    /** @return array<string, mixed> */
    public static function get(string $path): array
    {
        $response = self::application()->handle($path);
        TestCase::assertSame(200, $response->status);

        return json_decode($response->body(), associative: true, flags: JSON_THROW_ON_ERROR);
    }

    public static function application(): Application
    {
        $codecs = DefaultCodecs::create();
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), $codecs);
        $at = new DateTimeImmutable('2026-10-15T14:03:00+00:00');

        $store->record(array_map(static fn(Fact $fact): Observation => new Observation($fact, Source::Socket, $at), [
            new Fact(new MapName('forest'), new MapId(2)),
            new Fact(new MapName('forest'), new MapMonster(new MonsterId(2))),
            new Fact(new MapName('forest'), new MapExit(new MapName('fableforest'))),
            new Fact(new MonsterId(2), new MonsterName('Boss Zardman')),
            new Fact(new ItemId(100), new ItemName('Special Hair Gel')),
            new Fact(new QuestId(14), new QuestName('Specialty Pizza')),
            new Fact(new QuestId(14), new ItemReward(new ItemId(100), new DropRate(12.5), 1)),
            new Fact(new MapName('forest'), new MapNpc(new NpcId(new MapName('forest'), new Linkage('Twilly_APOP')))),
            new Fact(new NpcId(new MapName('forest'), new Linkage('Twilly_APOP')), new NpcName('Twilly')),
            new Fact(new NpcId(new MapName('forest'), new Linkage('Twilly_APOP')), new NpcQuest(new QuestId(14))),
            new Fact(new NpcId(new MapName('forest'), new Linkage('Twilly_APOP')), new NpcShop(new ShopId(31))),
            new Fact(new ShopId(31), new ShopName('Battleon Assorted shop')),
            new Fact(new ShopId(31), new ShopItem(new ItemId(100))),
            new Fact(new ShopId(31), new ShopItem(new ItemId(200), [new MergeCost(new ItemId(100), 3)])),
        ]));

        return new Application($store, $codecs);
    }
}
