<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Item;

use Lorekeeper\Server\Event\Item\ItemWornEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemWornEventTest extends TestCase
{
    #[Test]
    public function it_matches_a_real_message(): void
    {
        $event = ItemWornEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"wearItem","uid":93474,"sES":"Weapon","ItemID":91234,"sFile":"items/swords/MalakaiKatana.swf","sLink":"MalakaiKatana","sType":"Sword","success":true}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(93_474, $event->player->value);
        static::assertSame(91_234, $event->itemId->value);
        static::assertSame('Spawn 93474 wore item 91234', $event->summary());
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            ItemWornEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"unwearItem"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_item(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemWornEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"wearItem","uid":1}}}')->unwrap());
    }
}
