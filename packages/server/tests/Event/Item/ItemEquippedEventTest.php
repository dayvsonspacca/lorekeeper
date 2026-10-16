<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Item;

use Lorekeeper\Server\Event\Item\ItemEquippedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemEquippedEventTest extends TestCase
{
    #[Test]
    public function it_matches_a_real_message(): void
    {
        $event = ItemEquippedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"equipItem","uid":93426,"strES":"co","ItemID":31660,"sMeta":"cp:1.25,rep:1.25,gold:1.25,exp:1.25","sFile":"CelestialStarMage.swf","sLink":"CelestialStarMage","sType":"Armor"}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(93_426, $event->player->value);
        static::assertSame(31_660, $event->itemId->value);
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            ItemEquippedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"unequipItem"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_item(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemEquippedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":1}}}')->unwrap());
    }
}
