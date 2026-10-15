<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use DateTimeImmutable;
use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapLink;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapUnavailable;
use Lorekeeper\Miner\Discovery\MapQueue;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Observation;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class MapQueueTest extends TestCase
{
    #[Test]
    public function it_visits_known_maps_without_a_gamefile(): void
    {
        [$queue] = self::queue([
            ['battleon', new MapId(1), '2026-10-15'],
            ['battleon', new Gamefile('Battleon/town-Battleon-9Oct26.swf'), '2026-10-15'],
            ['forest', new MapId(2), '2026-10-15'],
            ['forest', new MapLink(new MapName('fableforest')), '2026-10-15'],
            ['shallow', new MapId(47), '2026-10-15'],
            ['shallow', new MemberOnly(), '2026-10-15'],
            ['house', new MapId(9), '2026-10-15'],
        ]);

        static::assertEquals([new MapName('forest'), new MapName('fableforest')], $queue->toVisit());
    }

    #[Test]
    public function it_retries_unavailable_maps_after_a_while(): void
    {
        [$queue] = self::queue([
            ['tlapd', new MapUnavailable(), '2026-10-14'],
            ['oldmap', new MapUnavailable(), '2026-09-01'],
            ['forest', new MapLink(new MapName('tlapd')), '2026-10-15'],
            ['forest', new MapLink(new MapName('oldmap')), '2026-10-15'],
        ]);

        static::assertEquals([new MapName('oldmap')], $queue->toVisit());
    }

    #[Test]
    public function it_analyzes_map_files_not_analyzed_yet(): void
    {
        [$queue] = self::queue([
            ['battleon', new Gamefile('Battleon/town-Battleon-9Oct26.swf'), '2026-10-15'],
            ['forest', new Gamefile('GreenGuard/town-Forest-13Aug24.swf'), '2026-10-15'],
            ['forest', new MapAnalyzed(new Gamefile('GreenGuard/town-Forest-13Aug24.swf')), '2026-10-15'],
            ['yulgar', new Gamefile('Yulgar/town-Yulgar-2Nov26.swf'), '2026-10-15'],
            ['yulgar', new MapAnalyzed(new Gamefile('Yulgar/town-Yulgar-1Jan20.swf')), '2026-10-15'],
        ]);

        static::assertEquals(
            [
                'battleon' => new Gamefile('Battleon/town-Battleon-9Oct26.swf'),
                'yulgar' => new Gamefile('Yulgar/town-Yulgar-2Nov26.swf'),
            ],
            $queue->toAnalyze(),
        );
    }

    /**
     * @param list<array{string, object, string}> $facts
     *
     * @return array{MapQueue}
     */
    private static function queue(array $facts): array
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $store->record(array_map(
            static fn(array $f): Observation => new Observation(
                new Fact(new MapName($f[0]), $f[1]),
                Source::Socket,
                new DateTimeImmutable("{$f[2]}T12:00:00+00:00"),
            ),
            $facts,
        ));

        return [new MapQueue(
            $store,
            static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15T12:00:00+00:00'),
            Duration::hours(7 * 24),
        )];
    }
}
