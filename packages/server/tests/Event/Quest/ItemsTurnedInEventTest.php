<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest;

use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemsTurnedInEventTest extends TestCase
{
    #[Test]
    public function it_reads_the_items_a_quest_took_back(): void
    {
        $event = ItemsTurnedInEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"turnIn","sItems":"42658:1"}}}')->unwrap(),
        )->unwrap();

        static::assertCount(1, $event->items);
        static::assertSame([42_658, 1], [$event->items[0]->item->value, $event->items[0]->quantity->value]);
        static::assertSame('Turned in 1 of item 42658', $event->summary());
    }

    #[Test]
    public function it_ignores_other_messages(): void
    {
        static::assertTrue(
            ItemsTurnedInEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"setAchievement","field":"id2","index":6,"value":1}}}',
                )->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function it_rejects_a_list_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        ItemsTurnedInEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"turnIn","sItems":"42658"}}}')->unwrap(),
        );
    }
}
