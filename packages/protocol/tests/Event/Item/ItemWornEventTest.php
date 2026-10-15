<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Item;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemType;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Protocol\Event\Item\ItemWornEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemWornEventTest extends TestCase
{
    #[Test]
    public function it_extracts_item_facts_from_a_real_message(): void
    {
        $message = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"wearItem","uid":93474,"sES":"Weapon","ItemID":91234,"sFile":"items/swords/MalakaiKatana.swf","sLink":"MalakaiKatana","sType":"Sword","success":true}}}',
        )->unwrap();

        $event = ItemWornEvent::from($message)->unwrap();
        $katana = new ItemId(91_234);

        static::assertSame(93_474, $event->player->value);
        static::assertEquals($katana, $event->itemId);
        static::assertEquals(
            [
                new Fact($katana, new Gamefile('items/swords/MalakaiKatana.swf')),
                new Fact($katana, new Linkage('MalakaiKatana')),
                new Fact($katana, new ItemType('Sword')),
                new Fact($katana, EquipmentSlot::Weapon),
            ],
            $event->facts(),
        );
    }

    #[Test]
    public function it_records_nothing_when_wearing_failed(): void
    {
        $event = ItemWornEvent::from(new JsonMessage([
            'cmd' => 'wearItem',
            'uid' => 93_474,
            'ItemID' => 91_234,
            'success' => false,
        ], ''))->unwrap();

        static::assertSame([], $event->facts());
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        $message = JsonMessage::from('{"b":{"o":{"cmd":"equipItem"}}}')->unwrap();

        static::assertTrue(ItemWornEvent::from($message)->isNone());
    }

    #[Test]
    public function it_rejects_messages_without_item(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemWornEvent::from(new JsonMessage(['cmd' => 'wearItem', 'uid' => 1], ''));
    }
}
