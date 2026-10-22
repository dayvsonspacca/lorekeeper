<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Server\Event\Player\PlayerAwayUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class PlayerAwayUpdatedEventTest extends TestCase
{
    private const string ENTERED = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"hero2","o":{"uoName":"hero2","ID":72894515,"entType":"p","entID":93474,"strUsername":"Hero2","intLevel":100,"intState":1,"intHP":3455,"intMP":100,"intSP":100,"intHPMax":3455,"intMPMax":100,"intSPMax":100,"showCloak":true,"showHelm":true,"tx":0,"ty":0,"afk":false,"strFrame":"Enter","strPad":"Spawn"}}}}
        JSON;

    #[Test]
    public function it_reads_a_player_going_away_and_coming_back(): void
    {
        $away = PlayerAwayUpdatedEvent::from(DelimitedMessage::from('%xt%uotls%-1%Hero%afk:true%')->unwrap())->unwrap();
        $back = PlayerAwayUpdatedEvent::from(
            DelimitedMessage::from('%xt%uotls%-1%Hero%afk:false%')->unwrap(),
        )->unwrap();

        static::assertInstanceOf(PlayerUpdatedEvent::class, $away);
        static::assertSame('Hero', $away->name->value);
        static::assertTrue($away->away);
        static::assertFalse($back->away);
        static::assertSame('Hero is away', $away->summary());
        static::assertSame('Hero is back', $back->summary());
    }

    #[Test]
    public function it_reads_whether_a_player_entering_the_room_is_away(): void
    {
        $event = PlayerAwayUpdatedEvent::from(JsonMessage::from(self::ENTERED)->unwrap())->unwrap();

        static::assertSame('hero2', $event->name->value);
        static::assertFalse($event->away);
    }

    #[Test]
    public function it_ignores_updates_that_say_nothing_about_being_away(): void
    {
        $walked = DelimitedMessage::from('%xt%uotls%-1%cerealhq%tx:739,ty:1,sp:8,strFrame:Enter%')->unwrap();
        $vitals = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"Hero","o":{"intHPMax":2585,"intMPMax":100,"intHP":2585,"intMP":100}}}}',
        )->unwrap();

        static::assertTrue(PlayerAwayUpdatedEvent::from($walked)->isNone());
        static::assertTrue(PlayerAwayUpdatedEvent::from($vitals)->isNone());
        static::assertTrue(
            PlayerAwayUpdatedEvent::from(DelimitedMessage::from('%xt%exitArea%-1%1%hero%')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_away_that_is_neither_true_nor_false(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("away is 'maybe', not true or false");

        PlayerAwayUpdatedEvent::from(DelimitedMessage::from('%xt%uotls%-1%hero%afk:maybe%')->unwrap());
    }

    #[Test]
    public function it_rejects_a_json_away_that_is_not_a_boolean(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('away is not true or false');

        PlayerAwayUpdatedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"hero2","o":{"afk":"maybe"}}}}',
            )->unwrap(),
        );
    }

    #[Test]
    #[TestWith(['{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","o":{"afk":false}}}}'])]
    #[TestWith(['{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"","o":{"afk":false}}}}'])]
    public function it_rejects_an_away_update_without_the_player(string $json): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('player name is missing or invalid');

        PlayerAwayUpdatedEvent::from(JsonMessage::from($json)->unwrap());
    }
}
