<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DropPickedUpEventTest extends TestCase
{
    private const string PICKED = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"getDrop","ItemID":42657,"bSuccess":1,"showDrop":1,"CharItemID":1442069609,"bBank":0,"iQty":1,"iQtyNow":1}}}
        JSON;

    #[Test]
    public function it_reads_the_item_picked_up_and_how_many_are_held_now(): void
    {
        $event = DropPickedUpEvent::from(JsonMessage::from(self::PICKED)->unwrap())->unwrap();

        static::assertSame([42_657, 1, 1], [$event->item->value, $event->picked->value, $event->held->value]);
        static::assertSame('Picked up 1 of item 42657, holding 1', $event->summary());
    }

    #[Test]
    public function it_says_nothing_when_the_pickup_failed(): void
    {
        static::assertTrue(
            DropPickedUpEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"getDrop","ItemID":42657,"bSuccess":0}}}',
                )->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function it_rejects_a_pickup_without_the_item(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('picked up drop is invalid');

        DropPickedUpEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"getDrop","bSuccess":1,"iQty":1,"iQtyNow":1}}}',
            )->unwrap(),
        );
    }

    #[Test]
    public function it_rejects_a_pickup_without_its_quantities(): void
    {
        $this->expectException(MalformedEventException::class);

        DropPickedUpEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"getDrop","ItemID":42657,"bSuccess":1}}}')->unwrap(),
        );
    }
}
