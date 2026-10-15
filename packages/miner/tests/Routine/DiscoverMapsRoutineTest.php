<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use DateTimeImmutable;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapMention;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Miner\Discovery\MapAnalysis;
use Lorekeeper\Miner\Discovery\MapDiscoverer;
use Lorekeeper\Miner\Discovery\MapQueue;
use Lorekeeper\Miner\Discovery\Npc;
use Lorekeeper\Miner\Discovery\PopupButtons;
use Lorekeeper\Miner\Discovery\PopupText;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Routine\DiscoverMapsRoutine;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class DiscoverMapsRoutineTest extends TestCase
{
    #[Test]
    public function it_analyzes_known_gamefiles_and_visits_new_maps(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $clock = static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15T12:00:00+00:00');
        $recorder = new Recorder($store, $clock);
        $recorder->record([
            new Fact(new MapName('forest'), new MapId(2)),
            new Fact(new MapName('forest'), new Gamefile('GreenGuard/town-Forest-13Aug24.swf')),
        ], Source::Socket);

        $discoverer = new class implements MapDiscoverer {
            public function discover(Gamefile $mapFile): MapAnalysis
            {
                return new MapAnalysis(
                    [new MapName('greenguardeast'), new MapName('forest'), new MapName('fableforest')],
                    [new MapExit(new MapName('greenguardeast')), new MapExit(new MapName('forest'))],
                    [new Npc(
                        new Linkage('Twilly_APOP'),
                        new PopupButtons([new QuestId(407)], [new ShopId(31)]),
                        new PopupText(new NpcName('Twilly'), null),
                    )],
                );
            }
        };

        $sent = [];
        $routine = new DiscoverMapsRoutine(
            RoutineStub::entry(static::createStub(TCP\StreamInterface::class), $sent, RoutineStub::readySession()),
            new MapQueue($store, $clock, Duration::hours(1)),
            $discoverer,
            $recorder,
            static fn(string $line): null => null,
        );

        $routine->run(Duration::zero(), 10, new SignalCancellationToken());

        static::assertEquals(
            [new MapMention(new MapName('greenguardeast')), new MapMention(new MapName('fableforest'))],
            array_map(static fn($stored): object => $stored->fact->value, $store->withValue(MapMention::class)),
        );
        static::assertSame(Source::Swf, $store->withValue(MapMention::class)[0]->source);
        static::assertEquals(
            [new MapExit(new MapName('greenguardeast'))],
            array_map(static fn($stored): object => $stored->fact->value, $store->withValue(MapExit::class)),
        );
        static::assertEquals(
            [new MapNpc(new NpcId(new MapName('forest'), new Linkage('Twilly_APOP')))],
            array_map(static fn($stored): object => $stored->fact->value, $store->withValue(MapNpc::class)),
        );
        static::assertEquals(
            [new NpcName('Twilly'), new NpcQuest(new QuestId(407)), new NpcShop(new ShopId(31))],
            array_map(
                static fn($stored): object => $stored->fact->value,
                $store->about(new NpcId(new MapName('forest'), new Linkage('Twilly_APOP'))),
            ),
        );
        static::assertCount(1, $store->withValue(MapAnalyzed::class));
        static::assertSame(
            ['%xt%zm%cmd%1%tfer%Hero%greenguardeast%', '%xt%zm%cmd%1%tfer%Hero%fableforest%', '%xt%zm%logout%1%'],
            array_slice($sent, offset: 4),
        );
    }

    #[Test]
    public function it_respects_the_visit_limit(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $clock = static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15T12:00:00+00:00');
        $recorder = new Recorder($store, $clock);
        $recorder->record([
            new Fact(new MapName('forest'), new MapId(2)),
            new Fact(new MapName('yulgar'), new MapId(3)),
            new Fact(new MapName('cellar'), new MapId(10)),
        ], Source::TravelMap);

        $sent = [];
        new DiscoverMapsRoutine(
            RoutineStub::entry(static::createStub(TCP\StreamInterface::class), $sent, RoutineStub::readySession()),
            new MapQueue($store, $clock, Duration::hours(1)),
            new class implements MapDiscoverer {
                public function discover(Gamefile $mapFile): MapAnalysis
                {
                    return new MapAnalysis([], [], []);
                }
            },
            $recorder,
            static fn(string $line): null => null,
        )->run(Duration::zero(), 2, new SignalCancellationToken());

        static::assertCount(2, array_filter($sent, static fn(string $m): bool => str_contains($m, '%tfer%')));
    }
}
