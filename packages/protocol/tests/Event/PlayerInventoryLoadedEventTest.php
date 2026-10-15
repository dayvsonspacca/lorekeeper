<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event;

use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Protocol\Event\PlayerInventoryLoadedEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerInventoryLoadedEventTest extends TestCase
{
    #[Test]
    public function it_extracts_facts_for_every_item(): void
    {
        $event = PlayerInventoryLoadedEvent::from(self::message([
            ['ItemID' => 1, 'sName' => 'Crag & Bamboozle', 'iCost' => 5000, 'bCoins' => true],
            ['ItemID' => 2, 'sName' => 'Chaoroot'],
        ]))->unwrap();

        static::assertEquals(
            [
                new Fact(new ItemId(1), new ItemName('Crag & Bamboozle')),
                new Fact(new ItemId(1), new ItemPrice(new AdventureCoins(5000))),
                new Fact(new ItemId(2), new ItemName('Chaoroot')),
            ],
            $event->facts,
        );
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        $message = JsonMessage::from('{"b":{"o":{"cmd":"moveToArea"}}}')->unwrap();

        static::assertTrue(PlayerInventoryLoadedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_delimited_message(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%data%')->unwrap();

        static::assertTrue(PlayerInventoryLoadedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_rejects_missing_items(): void
    {
        $message = JsonMessage::from('{"b":{"o":{"cmd":"loadInventoryBig"}}}')->unwrap();

        $this->expectException(MalformedEventException::class);

        PlayerInventoryLoadedEvent::from($message);
    }

    #[Test]
    public function it_rejects_items_without_name(): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerInventoryLoadedEvent::from(self::message([['ItemID' => 1]]));
    }

    /** @param list<array<string, mixed>> $items */
    private static function message(array $items): JsonMessage
    {
        return new JsonMessage(['cmd' => 'loadInventoryBig', 'items' => $items], '');
    }
}
