<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemsIncreasedEventTest extends TestCase
{
    private const string ANOTHER_GORILLAPHANT = '{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5574":{"ItemID":"5574","iQty":1}}}}}';

    private const string FIRST_GORILLAPHANT = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5574":{"ItemID":5574,"sName":"Slain Gorillaphant","sDesc":"","iRng":10,"iDPS":0,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":57,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"795,802","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{},"iQty":1}}}}}
        JSON;

    #[Test]
    public function more_of_a_quest_item_already_held_comes_with_its_id_and_quantity_only(): void
    {
        $event = QuestItemsIncreasedEvent::from(JsonMessage::from(self::ANOTHER_GORILLAPHANT)->unwrap())->unwrap();

        static::assertSame([5574, 1], [$event->items[0]->item->value, $event->items[0]->quantity->value]);
        static::assertSame('Got for now 1 more of item 5574', $event->summary());
        static::assertTrue(
            ItemsIncreasedEvent::from(JsonMessage::from(self::ANOTHER_GORILLAPHANT)->unwrap())->isNone(),
        );
        static::assertTrue(
            QuestItemsReceivedEvent::from(JsonMessage::from(self::ANOTHER_GORILLAPHANT)->unwrap())->isNone(),
        );
    }

    #[Test]
    public function a_reward_already_held_comes_with_how_many_are_held_now(): void
    {
        $event = ItemsIncreasedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5586":{"iQty":1,"iQtyNow":11,"CharItemID":695329010,"bBank":0}},"msg":""}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame([5586, 1, 11], [
            $event->items[0]->item->value,
            $event->items[0]->quantity->value,
            $event->items[0]->held?->value,
        ]);
        static::assertSame('Got 1 more of item 5586, 11 held now', $event->summary());
        static::assertTrue(
            QuestItemsIncreasedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5586":{"iQty":1,"iQtyNow":11,"CharItemID":695329010,"bBank":0}},"msg":""}}}',
                )->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function a_new_kept_item_straight_into_the_inventory_is_not_read_yet(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('a new item came straight into the inventory');

        ItemsIncreasedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"9392":{"ItemID":9392,"sName":"This Might Be A Token","iLvl":1,"bTemp":false,"sType":"Item","iQty":1}}}}}',
            )->unwrap(),
        );
    }

    #[Test]
    public function an_item_that_matches_no_known_shape_is_refused(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('an added item matches no known shape');

        ItemsIncreasedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5586":{"iQty":1}}}}}')->unwrap(),
        );
    }

    #[Test]
    public function it_rejects_items_without_a_quantity(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('items are missing or invalid');

        ItemsIncreasedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5574":{"ItemID":"5574"}}}}}',
            )->unwrap(),
        );
    }

    #[Test]
    public function the_first_of_an_item_comes_whole_and_is_no_more_of_anything(): void
    {
        static::assertTrue(ItemsIncreasedEvent::from(JsonMessage::from(self::FIRST_GORILLAPHANT)->unwrap())->isNone());
        static::assertSame(
            'Slain Gorillaphant',
            QuestItemsReceivedEvent::from(
                JsonMessage::from(self::FIRST_GORILLAPHANT)->unwrap(),
            )->unwrap()->items[0]->item->name->value,
        );
    }
}
