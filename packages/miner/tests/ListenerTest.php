<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests;

use DateTimeImmutable;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Miner\Connection;
use Lorekeeper\Miner\Listener;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Session;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use Lorekeeper\Protocol\Message\Message;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\TCP;

final class ListenerTest extends TestCase
{
    private const string LOGIN = '%xt%loginResponse%-1%true%59878%hero%Welcome!%';

    private const string INVENTORY = '{"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":447,"sName":"Sweeping Broom"}]}}}';

    private const string MALFORMED = '{"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig"}}}';

    #[Test]
    public function it_records_facts_from_data_events(): void
    {
        [$listener, $store] = self::listener([self::INVENTORY]);

        $listener->listen();

        $stored = $store->about(new ItemId(447));
        static::assertCount(1, $stored);
        static::assertEquals(new ItemName('Sweeping Broom'), $stored[0]->fact->value);
        static::assertSame(Source::Socket, $stored[0]->source);
        static::assertEquals(new DateTimeImmutable('2026-10-15T10:00:00+00:00'), $stored[0]->firstSeen);
    }

    #[Test]
    public function it_updates_the_session(): void
    {
        $session = new Session();
        [$listener] = self::listener([self::LOGIN], $session);

        $listener->listen();

        static::assertSame(59_878, $session->socketId()->unwrap()->value);
    }

    #[Test]
    public function it_reports_messages_no_event_recognizes(): void
    {
        $log = [];
        $unrecognized = [];
        [$listener] = self::listener(
            ['%xt%uotls%-1%someone%tx:0,ty:0%', self::INVENTORY],
            log: $log,
            unrecognized: $unrecognized,
        );

        $listener->listen();

        static::assertSame(['%xt%uotls%-1%someone%tx:0,ty:0%'], $unrecognized);
    }

    #[Test]
    public function it_keeps_listening_after_malformed_and_unknown_messages(): void
    {
        $lines = [];
        [$listener, $store] = self::listener(['hello', self::MALFORMED, self::INVENTORY], log: $lines);

        $listener->listen();

        static::assertCount(1, $store->about(new ItemId(447)));
        static::assertSame(['messages' => 3, 'events' => 1, 'facts' => 1], $listener->stats());
        static::assertStringStartsWith('! Malformed', $lines[0]);
    }

    /**
     * @param list<string> $messages
     * @param list<string> $log
     * @param list<string> $unrecognized
     *
     * @return array{Listener, SqliteFactStore}
     */
    private static function listener(
        array $messages,
        Session $session = new Session(),
        array &$log = [],
        array &$unrecognized = [],
    ): array {
        $stream = static::createStub(TCP\StreamInterface::class);
        $stream->method('read')->willReturnOnConsecutiveCalls(
            implode('', array_map(static fn(string $m): string => "{$m}\0", $messages)),
            '',
        );
        $stream->method('reachedEndOfDataSource')->willReturn(true);

        $store = new SqliteFactStore(new PDO('sqlite::memory:'), DefaultCodecs::create());
        $listener = new Listener(
            new Connection($stream),
            new Recorder($store, static fn(): DateTimeImmutable => new DateTimeImmutable('2026-10-15T10:00:00+00:00')),
            $session,
            static function (string $line) use (&$log): void {
                $log[] = $line;
            },
            static function (Message $message) use (&$unrecognized): void {
                $unrecognized[] = $message->raw;
            },
        );

        return [$listener, $store];
    }
}
