<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use DateTimeImmutable;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionName;
use Lorekeeper\Miner\Exception\TravelMapUnavailableException;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Routine\TravelMapRoutine;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TravelMapRoutineTest extends TestCase
{
    #[Test]
    public function it_records_the_travel_map_facts(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $routine = new TravelMapRoutine(
            static fn(): array => [
                new Fact(new RegionId(1), new RegionName('Greenguard')),
                new Fact(new MapName('forest'), new MapId(2)),
            ],
            new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15T10:00:00+00:00')),
            static fn(string $line): null => null,
        );

        static::assertSame(2, $routine->run());

        $stored = $store->about(new MapName('forest'));
        static::assertEquals(new MapId(2), $stored[0]->fact->value);
        static::assertSame(Source::TravelMap, $stored[0]->source);
    }

    #[Test]
    public function it_records_nothing_when_the_travel_map_is_unavailable(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $routine = new TravelMapRoutine(
            static fn(): array => throw new TravelMapUnavailableException('down'),
            new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable()),
            static fn(string $line): null => null,
        );

        try {
            $routine->run();
            static::fail('Expected the travel map to be unavailable.');
        } catch (TravelMapUnavailableException) {
            static::assertSame([], $store->about(new MapName('forest')));
        }
    }
}
