<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Server\Event\Player\PlayerMovedEvent;
use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class PlayerMovedEventTest extends TestCase
{
    private const string ENTERED = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"hero2","o":{"uoName":"hero2","ID":72894515,"entType":"p","entID":93474,"strUsername":"Hero2","intLevel":100,"intState":1,"intHP":3455,"intMP":100,"intSP":100,"intHPMax":3455,"intMPMax":100,"intSPMax":100,"showCloak":true,"showHelm":true,"tx":0,"ty":0,"afk":false,"strFrame":"Enter","strPad":"Spawn"}}}}
        JSON;

    #[Test]
    public function it_reads_where_a_player_entering_the_room_stands(): void
    {
        $event = PlayerMovedEvent::from(JsonMessage::from(self::ENTERED)->unwrap())->unwrap();

        static::assertInstanceOf(PlayerUpdatedEvent::class, $event);
        static::assertSame('hero2', $event->name->value);
        static::assertSame('Enter', $event->spot->cell->value);
        static::assertSame(MapPad::Spawn, $event->spot->pad);
        static::assertSame('hero2 moved to Enter (Spawn)', $event->summary());
    }

    #[Test]
    public function it_reads_a_player_going_to_another_cell(): void
    {
        $event = PlayerMovedEvent::from(
            DelimitedMessage::from(
                '%xt%uotls%-1%hero%strFrame:r2,strPad:Right,px:500,py:375,mvts:-1,mvtd:0,tx:0,ty:0,bResting:false%',
            )->unwrap(),
        )->unwrap();

        static::assertSame('hero', $event->name->value);
        static::assertSame('r2', $event->spot->cell->value);
        static::assertSame(MapPad::Right, $event->spot->pad);
    }

    #[Test]
    public function it_ignores_updates_that_say_nothing_about_a_cell(): void
    {
        $walked = DelimitedMessage::from('%xt%uotls%-1%hero%tx:739,ty:1,sp:8,strFrame:Enter%')->unwrap();
        $vitals = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"Hero","o":{"intHPMax":2585,"intMPMax":100,"intHP":2585,"intMP":100}}}}',
        )->unwrap();

        static::assertTrue(PlayerMovedEvent::from($walked)->isNone());
        static::assertTrue(PlayerMovedEvent::from($vitals)->isNone());
        static::assertTrue(
            PlayerMovedEvent::from(DelimitedMessage::from('%xt%exitArea%-1%1%hero%')->unwrap())->isNone(),
        );
    }

    #[Test]
    #[TestWith(['%xt%uotls%-1%hero%strPad:Right%'])]
    #[TestWith(['{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","o":{"strFrame":"r2","strPad":"Right"}}}}'])]
    #[TestWith(['{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"hero","o":{"strFrame":"","strPad":"Right"}}}}'])]
    public function it_rejects_a_move_without_the_player_or_the_cell(string $message): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('player name, cell or pad is missing');

        PlayerMovedEvent::from(self::message($message));
    }

    #[Test]
    public function it_rejects_a_pad_it_does_not_know(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown pad 'Sideways'");

        PlayerMovedEvent::from(DelimitedMessage::from('%xt%uotls%-1%hero%strFrame:r2,strPad:Sideways%')->unwrap());
    }

    #[Test]
    public function it_rejects_an_update_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('player update is invalid');

        PlayerMovedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"hero","o":"r2"}}}')->unwrap(),
        );
    }

    private static function message(string $message): Message
    {
        return str_starts_with($message, '{')
            ? JsonMessage::from($message)->unwrap()
            : DelimitedMessage::from($message)->unwrap();
    }
}
