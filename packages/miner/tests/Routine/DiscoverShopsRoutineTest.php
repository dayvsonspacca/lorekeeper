<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use DateTimeImmutable;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopUnavailable;
use Lorekeeper\Miner\Discovery\ShopQueue;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Routine\DiscoverShopsRoutine;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class DiscoverShopsRoutineTest extends TestCase
{
    #[Test]
    public function it_opens_each_shop_from_the_map_it_belongs_to(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15'))->record([
            new Fact(new NpcId(new MapName('battleon'), new Linkage('Twilly_APOP')), new NpcShop(new ShopId(31))),
            new Fact(new NpcId(new MapName('battleon'), new Linkage('Ragnar_APOP')), new NpcShop(new ShopId(299))),
            new Fact(new NpcId(new MapName('yulgar'), new Linkage('Yulgar_APOP')), new NpcShop(new ShopId(10))),
        ], Source::Swf);

        $sent = [];
        new DiscoverShopsRoutine(
            RoutineStub::entry(
                static::createStub(TCP\StreamInterface::class),
                $sent,
                RoutineStub::readySession(),
                Duration::milliseconds(200),
            ),
            new ShopQueue(
                $store,
                static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15'),
                Duration::hours(1),
            ),
            new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15')),
            static fn(string $line): null => null,
        )->run(Duration::zero(), 5, new SignalCancellationToken());

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%battleon%',
                '%xt%zm%loadShop%172760%31%',
                '%xt%zm%loadShop%172760%299%',
                '%xt%zm%cmd%1%tfer%Hero%yulgar%',
                '%xt%zm%logout%1%',
            ],
            array_slice($sent, offset: 4),
        );
        static::assertCount(2, $store->withValue(ShopUnavailable::class));
    }
}
