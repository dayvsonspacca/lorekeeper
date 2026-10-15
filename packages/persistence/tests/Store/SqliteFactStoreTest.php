<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Tests\Store;

use DateTimeImmutable;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Exception\UnknownTypeException;
use Lorekeeper\Persistence\Observation\Observation;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use Lorekeeper\Persistence\Store\StoredFact;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

final class SqliteFactStoreTest extends TestCase
{
    #[Test]
    public function it_records_and_reads_back_a_fact(): void
    {
        $store = self::store();
        $fact = new Fact(new ItemId(447), new ItemName('Sweeping Broom'));

        $store->record([new Observation($fact, Source::Socket, self::at('2026-10-15 10:00'))]);

        static::assertEquals(
            [new StoredFact($fact, Source::Socket, self::at('2026-10-15 10:00'), self::at('2026-10-15 10:00'), 1)],
            $store->about(new ItemId(447)),
        );
    }

    #[Test]
    public function it_counts_repeated_observations_of_the_same_fact(): void
    {
        $store = self::store();
        $fact = new Fact(new ItemId(447), new ItemName('Sweeping Broom'));

        $store->record([new Observation($fact, Source::Socket, self::at('2026-10-15 10:00'))]);
        $store->record([new Observation($fact, Source::Socket, self::at('2026-10-15 12:00'))]);
        $store->record([new Observation($fact, Source::Socket, self::at('2026-10-14 08:00'))]);

        static::assertEquals(
            [new StoredFact($fact, Source::Socket, self::at('2026-10-14 08:00'), self::at('2026-10-15 12:00'), 3)],
            $store->about(new ItemId(447)),
        );
    }

    #[Test]
    public function it_keeps_history_when_a_value_changes(): void
    {
        $store = self::store();
        $crag = new ItemId(1);

        $store->record([new Observation(
            new Fact($crag, new ItemPrice(new AdventureCoins(4000))),
            Source::Socket,
            self::at('2026-01-01 00:00'),
        )]);
        $store->record([new Observation(
            new Fact($crag, new ItemPrice(new AdventureCoins(5000))),
            Source::Socket,
            self::at('2026-10-15 00:00'),
        )]);

        static::assertEquals(
            [new ItemPrice(new AdventureCoins(4000)), new ItemPrice(new AdventureCoins(5000))],
            array_map(static fn(StoredFact $stored): object => $stored->fact->value, $store->about($crag)),
        );
    }

    #[Test]
    public function it_keeps_the_same_fact_from_different_sources_apart(): void
    {
        $store = self::store();
        $fact = new Fact(new ItemId(447), new ItemName('Sweeping Broom'));

        $store->record([
            new Observation($fact, Source::Socket, self::at('2026-10-15 10:00')),
            new Observation($fact, Source::Manual, self::at('2026-10-15 10:00')),
        ]);

        static::assertSame(
            [Source::Socket, Source::Manual],
            array_map(static fn(StoredFact $stored): Source => $stored->source, $store->about(new ItemId(447))),
        );
    }

    #[Test]
    public function it_keeps_multiple_values_for_the_same_subject(): void
    {
        $store = self::store();
        $forest = new MapName('forest');

        $store->record([
            new Observation(
                new Fact($forest, new MapMonster(new MonsterId(1))),
                Source::Socket,
                self::at('2026-10-15 10:00'),
            ),
            new Observation(
                new Fact($forest, new MapMonster(new MonsterId(32))),
                Source::Socket,
                self::at('2026-10-15 10:00'),
            ),
            new Observation(
                new Fact(new MapName('battleon'), new MapMonster(new MonsterId(7))),
                Source::Socket,
                self::at('2026-10-15 10:00'),
            ),
        ]);

        static::assertEquals(
            [new MapMonster(new MonsterId(1)), new MapMonster(new MonsterId(32))],
            array_map(static fn(StoredFact $stored): object => $stored->fact->value, $store->about($forest)),
        );
    }

    #[Test]
    public function it_finds_facts_by_value_type(): void
    {
        $store = self::store();

        $store->record([
            new Observation(
                new Fact(new MapName('forest'), new MapMonster(new MonsterId(1))),
                Source::Socket,
                self::at('2026-10-15 10:00'),
            ),
            new Observation(
                new Fact(new MapName('forest'), new ItemName('not a monster')),
                Source::Socket,
                self::at('2026-10-15 10:00'),
            ),
            new Observation(
                new Fact(new MapName('battleon'), new MapMonster(new MonsterId(7))),
                Source::Socket,
                self::at('2026-10-15 10:00'),
            ),
        ]);

        static::assertEquals(
            [
                new Fact(new MapName('forest'), new MapMonster(new MonsterId(1))),
                new Fact(new MapName('battleon'), new MapMonster(new MonsterId(7))),
            ],
            array_map(static fn(StoredFact $stored): Fact => $stored->fact, $store->withValue(MapMonster::class)),
        );
    }

    #[Test]
    public function it_records_nothing_when_a_batch_has_an_unknown_value(): void
    {
        $store = self::store();

        try {
            $store->record([
                new Observation(
                    new Fact(new ItemId(447), new ItemName('Sweeping Broom')),
                    Source::Socket,
                    self::at('2026-10-15 10:00'),
                ),
                new Observation(
                    new Fact(new ItemId(447), new stdClass()),
                    Source::Socket,
                    self::at('2026-10-15 10:00'),
                ),
            ]);
            static::fail('Expected an unknown type.');
        } catch (UnknownTypeException) {
            static::assertSame([], $store->about(new ItemId(447)));
        }
    }

    #[Test]
    public function it_persists_to_a_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), prefix: 'lorekeeper');
        $fact = new Fact(new ItemId(447), new ItemName('Sweeping Broom'));

        SqliteFactStore::open($path, DefaultCodecs::create())->record([new Observation(
            $fact,
            Source::Socket,
            self::at('2026-10-15 10:00'),
        )]);

        static::assertCount(1, SqliteFactStore::open($path, DefaultCodecs::create())->about(new ItemId(447)));

        unlink($path);
    }

    private static function store(): SqliteFactStore
    {
        return new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
    }

    private static function at(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable("{$time}:00+00:00");
    }
}
