<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Map\RoomJoinedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoomJoinedEventTest extends TestCase
{
    private const string JOINED = "<msg t='sys'><body action='joinOK' r='227613'><pid id='1'/><vars /><uLs r='227613'><u i='75310' m='0' s='0' p='1'><n><![CDATA[hero]]></n><vars></vars></u><u i='75311' m='0' s='0' p='1'><n><![CDATA[hero 2]]></n><vars></vars></u></uLs></body></msg>";

    #[Test]
    public function it_reads_who_is_in_the_joined_room(): void
    {
        $event = RoomJoinedEvent::from(XmlMessage::from(self::JOINED)->unwrap())->unwrap();

        static::assertEquals([new PlayerName('hero'), new PlayerName('hero 2')], $event->players);
        static::assertSame('Joined a room with 2 players', $event->summary());
    }

    #[Test]
    public function it_reads_an_empty_room(): void
    {
        $message = XmlMessage::from(
            "<msg t='sys'><body action='joinOK' r='1'><pid id='1'/><vars /><uLs r='1'></uLs></body></msg>",
        )->unwrap();

        static::assertSame([], RoomJoinedEvent::from($message)->unwrap()->players);
    }

    #[Test]
    public function it_ignores_other_messages(): void
    {
        $message = XmlMessage::from(
            "<msg t='sys'><body action='uER' r='1'><u i='1'><n><![CDATA[hero]]></n></u></body></msg>",
        )->unwrap();

        static::assertTrue(RoomJoinedEvent::from($message)->isNone());
        static::assertTrue(RoomJoinedEvent::from(DelimitedMessage::from('%xt%joinOK%')->unwrap())->isNone());
    }

    #[Test]
    public function it_rejects_a_player_without_name(): void
    {
        $message = XmlMessage::from(
            "<msg t='sys'><body action='joinOK' r='1'><uLs r='1'><u i='1'></u></uLs></body></msg>",
        )->unwrap();

        $this->expectException(MalformedEventException::class);

        RoomJoinedEvent::from($message);
    }
}
