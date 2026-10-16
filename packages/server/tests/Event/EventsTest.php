<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event;

use Lorekeeper\Server\Event\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Item\ItemEquippedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    #[Test]
    public function it_knows_every_event_of_the_package(): void
    {
        $files = glob(__DIR__ . '/../../src/Event/{,*/}*Event.php', GLOB_BRACE);
        $concrete = array_values(array_filter(
            array_map(
                static fn(string $file): string => basename($file, suffix: '.php'),
                $files === false ? [] : $files,
            ),
            static fn(string $class): bool => !in_array($class, ['Event', 'NoticeEvent'], strict: true),
        ));
        $known = array_map(static fn(string $event): string => substr(
            $event,
            (int) strrpos($event, needle: '\\') + 1,
        ), Events::ALL);

        sort($concrete);
        sort($known);

        static::assertSame($concrete, $known);
        foreach (Events::ALL as $event) {
            static::assertTrue(is_subclass_of($event, Event::class));
        }
    }

    #[Test]
    public function it_recognizes_what_a_message_is(): void
    {
        $policy = Events::recognize(XmlMessage::from('<cross-domain-policy/>')->unwrap());
        $equip = Events::recognize(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":1,"ItemID":2}}}')->unwrap());

        static::assertCount(1, $policy);
        static::assertInstanceOf(ConnectionEstablishedEvent::class, $policy[0]);
        static::assertInstanceOf(ItemEquippedEvent::class, $equip[0]);
        static::assertSame([], Events::recognize(DelimitedMessage::from('%xt%uotls%-1%hero%afk:true%')->unwrap()));
    }

    #[Test]
    public function it_reports_messages_it_cannot_read_instead_of_failing(): void
    {
        $recognized = Events::recognize(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":1}}}')->unwrap());

        static::assertCount(1, $recognized);
        static::assertInstanceOf(MalformedEventException::class, $recognized[0]);
    }
}
