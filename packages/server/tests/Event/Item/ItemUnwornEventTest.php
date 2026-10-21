<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Item;

use Lorekeeper\Server\Event\Item\ItemUnwornEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemUnwornEventTest extends TestCase
{
    #[Test]
    public function it_matches_a_real_message(): void
    {
        $event = ItemUnwornEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"unwearItem","uid":93474,"sES":"Weapon","ItemID":91234,"sFile":"items/polearms/MihuriInterstellarpolearm.swf","sLink":"MihuriInterstellarpolearm","sType":"Polearm","success":true}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(93_474, $event->player->value);
        static::assertSame(91_234, $event->itemId->value);
        static::assertSame('Socket 93474 took off item 91234', $event->summary());
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            ItemUnwornEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"wearItem"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_item(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemUnwornEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"unwearItem","uid":1}}}')->unwrap());
    }
}
