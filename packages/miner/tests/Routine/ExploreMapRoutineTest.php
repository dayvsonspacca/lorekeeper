<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use DateTimeImmutable;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopUnavailable;
use Lorekeeper\Miner\Discovery\MapAnalysis;
use Lorekeeper\Miner\Discovery\MapDiscoverer;
use Lorekeeper\Miner\Discovery\Npc;
use Lorekeeper\Miner\Discovery\PopupButtons;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Routine\ExploreMapRoutine;
use Lorekeeper\Miner\Routine\MapVisit;
use Lorekeeper\Miner\Session;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Event\LoginSucceededEvent;
use Lorekeeper\Protocol\Event\Map\MovedToMapEvent;
use Lorekeeper\Protocol\RoomId;
use Lorekeeper\Protocol\SocketId;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class ExploreMapRoutineTest extends TestCase
{
    #[Test]
    public function it_joins_analyzes_and_asks_everything_the_map_offers(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $recorder = new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15'));
        $map = new MapName('battleontown');
        $file = new Gamefile('battleon/town-Battleontown-1Oct26r1.swf');

        $session = new Session();
        $session->observe(new LoginSucceededEvent(new SocketId(59_878)));
        $session->observe(new MovedToMapEvent(new AreaId(172_760), new RoomId(1), $map, [new Fact($map, $file)]));

        $sent = [];
        $entry = RoutineStub::entry(
            static::createStub(TCP\StreamInterface::class),
            $sent,
            $session,
            Duration::milliseconds(200),
        );

        new ExploreMapRoutine(
            $entry,
            new MapVisit($entry, $recorder, static fn(string $line): null => null),
            new class implements MapDiscoverer {
                public function discover(Gamefile $mapFile): MapAnalysis
                {
                    return new MapAnalysis(
                        [new MapName('librarium')],
                        [new MapExit(new MapName('librarium'))],
                        [
                            new Npc(
                                new Linkage('Twilly_APOP'),
                                new PopupButtons([new QuestId(407), new QuestId(408)], [new ShopId(31)]),
                            ),
                            new Npc(new Linkage('Joy_APOP'), new PopupButtons([new QuestId(2903)], [new ShopId(31)])),
                        ],
                    );
                }
            },
            $recorder,
            static fn(string $line): null => null,
        )->run($map, Duration::zero(), new SignalCancellationToken());

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%battleontown%',
                '%xt%zm%getQuests%172760%407%408%',
                '%xt%zm%getQuests%172760%2903%',
                '%xt%zm%loadShop%172760%31%',
                '%xt%zm%logout%1%',
            ],
            array_slice($sent, offset: 4),
        );
        static::assertCount(1, $store->withValue(MapExit::class));
        static::assertEquals(new MapAnalyzed($file), $store->withValue(MapAnalyzed::class)[0]->fact->value);
        static::assertCount(1, $store->withValue(ShopUnavailable::class));
    }
}
