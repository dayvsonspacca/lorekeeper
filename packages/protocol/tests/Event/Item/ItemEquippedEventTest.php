<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Item;

use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Protocol\Event\Item\ItemEquippedEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemEquippedEventTest extends TestCase
{
    #[Test]
    public function it_only_trusts_the_slot_because_the_file_is_what_is_shown(): void
    {
        $message = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"equipItem","uid":93426,"strES":"co","ItemID":31660,"sMeta":"cp:1.25,rep:1.25,gold:1.25,exp:1.25","sFile":"CelestialStarMage.swf","sLink":"CelestialStarMage","sType":"Armor"}}}',
        )->unwrap();

        $event = ItemEquippedEvent::from($message)->unwrap();
        $darkMage = new ItemId(31_660);

        static::assertSame(93_426, $event->player->value);
        static::assertEquals($darkMage, $event->itemId);
        static::assertEquals([new Fact($darkMage, EquipmentSlot::Armor)], $event->facts());
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        $message = JsonMessage::from('{"b":{"o":{"cmd":"moveToArea"}}}')->unwrap();

        static::assertTrue(ItemEquippedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_rejects_messages_without_item(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemEquippedEvent::from(new JsonMessage(['cmd' => 'equipItem', 'uid' => 1], ''));
    }
}
