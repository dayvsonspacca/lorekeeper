<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests;

use DateTimeImmutable;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RecorderTest extends TestCase
{
    #[Test]
    public function it_records_facts_with_source_and_time(): void
    {
        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $recorder = new Recorder(
            $store,
            static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15T10:00:00+00:00'),
        );

        $recorder->record([new Fact(new MapName('forest'), new MapId(2))], Source::TravelMap);

        $stored = $store->about(new MapName('forest'))[0];
        static::assertEquals(new MapId(2), $stored->fact->value);
        static::assertSame(Source::TravelMap, $stored->source);
        static::assertEquals(new DateTimeImmutable('2026-10-15T10:00:00+00:00'), $stored->lastSeen);
    }
}
