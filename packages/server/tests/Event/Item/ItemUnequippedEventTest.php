<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Item;

use Lorekeeper\Server\Event\Item\ItemUnequippedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemUnequippedEventTest extends TestCase
{
    #[Test]
    public function it_matches_a_real_message(): void
    {
        $event = ItemUnequippedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"unequipItem","uid":93474,"ItemID":35397,"strES":"co","bUnload":false}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(93_474, $event->player->value);
        static::assertSame(35_397, $event->itemId->value);
        static::assertSame('Socket 93474 unequipped item 35397', $event->summary());
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            ItemUnequippedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"equipItem"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_item(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemUnequippedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"unequipItem","uid":1}}}')->unwrap());
    }
}
