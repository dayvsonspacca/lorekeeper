<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TemporaryItemsAddedEventTest extends TestCase
{
    private const string METAL_ORE = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"42658":{"ItemID":42658,"sName":"Metal Ore","sDesc":"It's a chunk of metal. Take this back to Murp!","iRng":10,"iDPS":100,"iLvl":1,"iRty":13,"iCost":0,"bTemp":true,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"6187","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{},"iQty":1}}}}}
        JSON;

    #[Test]
    public function it_reads_the_quest_item_a_monster_left_for_now(): void
    {
        $event = TemporaryItemsAddedEvent::from(JsonMessage::from(self::METAL_ORE)->unwrap())->unwrap();

        static::assertCount(1, $event->items);
        static::assertInstanceOf(Resource::class, $event->items[0]->item);
        static::assertSame([42_658, 'Metal Ore', 1], [
            $event->items[0]->item->id->value,
            $event->items[0]->item->name->value,
            $event->items[0]->quantity->value,
        ]);
        static::assertSame('Got for now 1 Metal Ore', $event->summary());
        static::assertSame('TemporaryItemsAdded', $event->name());
    }

    #[Test]
    public function it_ignores_other_messages(): void
    {
        static::assertTrue(
            TemporaryItemsAddedEvent::from(
                JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"turnIn","sItems":"42658:1"}}}')->unwrap(),
            )->isNone(),
        );
        static::assertTrue(
            TemporaryItemsAddedEvent::from(DelimitedMessage::from('%xt%server%-1%hi%')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_items_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        TemporaryItemsAddedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"42658":{"ItemID":42658}}}}}',
            )->unwrap(),
        );
    }
}
