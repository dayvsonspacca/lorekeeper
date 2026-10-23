<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Server\Event\Player\PlayerEnteredEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class PlayerEnteredEventTest extends TestCase
{
    private const string ENTERED = "<msg t='sys'><body action='uER' r='172760'><u i='75477' m='0' s='0' p='2'><n><![CDATA[hero]]></n><vars></vars></u></body></msg>";

    #[Test]
    public function it_reads_who_entered_the_room(): void
    {
        $event = PlayerEnteredEvent::from(XmlMessage::from(self::ENTERED)->unwrap())->unwrap();

        static::assertSame(75_477, $event->spawnId->value);
        static::assertSame('hero', $event->name->value);
        static::assertSame('hero (spawn 75477) entered', $event->summary());
    }

    #[Test]
    public function it_ignores_other_actions(): void
    {
        $message = XmlMessage::from(
            "<msg t='sys'><body action='userGone' r='2'><user id='75584' /></body></msg>",
        )->unwrap();

        static::assertTrue(PlayerEnteredEvent::from($message)->isNone());
        static::assertTrue(PlayerEnteredEvent::from(XmlMessage::from('<cross-domain-policy/>')->unwrap())->isNone());
    }

    #[Test]
    public function it_ignores_delimited_message(): void
    {
        static::assertTrue(PlayerEnteredEvent::from(DelimitedMessage::from('%xt%uER%-1%')->unwrap())->isNone());
    }

    #[Test]
    #[TestWith(["<msg t='sys'><body action='uER' r='1'></body></msg>"])]
    #[TestWith(["<msg t='sys'><body action='uER' r='1'><u i='abc'><n><![CDATA[hero]]></n></u></body></msg>"])]
    #[TestWith(["<msg t='sys'><body action='uER' r='1'><u i='75477'><n></n></u></body></msg>"])]
    #[TestWith(["<msg t='sys'><body action='uER' r='1'><u i='75477'></u></body></msg>"])]
    public function it_rejects_a_player_it_cannot_read(string $xml): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerEnteredEvent::from(XmlMessage::from($xml)->unwrap());
    }
}
