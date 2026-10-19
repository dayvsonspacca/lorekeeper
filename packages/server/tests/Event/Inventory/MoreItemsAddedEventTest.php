<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Server\Event\Inventory\MoreItemsAddedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoreItemsAddedEventTest extends TestCase
{
    private const string ANOTHER_GORILLAPHANT = '{"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5574":{"ItemID":"5574","iQty":1}}}}}';

    private const string FIRST_GORILLAPHANT = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"addItems","items":{"5574":{"ItemID":5574,"sName":"Slain Gorillaphant","sDesc":"","iRng":10,"iDPS":0,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":57,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"795,802","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{},"iQty":1}}}}}
        JSON;

    #[Test]
    public function more_of_an_item_already_held_comes_with_its_id_and_quantity_only(): void
    {
        $event = MoreItemsAddedEvent::from(JsonMessage::from(self::ANOTHER_GORILLAPHANT)->unwrap())->unwrap();

        static::assertSame([5574, 1], [$event->items[0]->item->value, $event->items[0]->quantity->value]);
        static::assertSame('Got 1 more of item 5574', $event->summary());
        static::assertTrue(
            TemporaryItemsAddedEvent::from(JsonMessage::from(self::ANOTHER_GORILLAPHANT)->unwrap())->isNone(),
        );
    }

    #[Test]
    public function a_reward_already_held_comes_with_how_many_are_held_now(): void
    {
        $event = MoreItemsAddedEvent::from(
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
    }

    #[Test]
    public function the_first_of_an_item_comes_whole_and_is_no_more_of_anything(): void
    {
        static::assertTrue(MoreItemsAddedEvent::from(JsonMessage::from(self::FIRST_GORILLAPHANT)->unwrap())->isNone());
        static::assertSame(
            'Slain Gorillaphant',
            TemporaryItemsAddedEvent::from(
                JsonMessage::from(self::FIRST_GORILLAPHANT)->unwrap(),
            )->unwrap()->items[0]->item->name->value,
        );
    }
}
