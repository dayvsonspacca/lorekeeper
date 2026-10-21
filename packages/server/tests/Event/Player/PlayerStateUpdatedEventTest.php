<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerStateUpdatedEventTest extends TestCase
{
    #[Test]
    public function it_reads_a_player_leaving_combat(): void
    {
        $event = PlayerStateUpdatedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"lorekeeper03","o":{"intState":1}}}}',
            )->unwrap(),
        )->unwrap();

        static::assertInstanceOf(PlayerUpdatedEvent::class, $event);
        static::assertSame('lorekeeper03', $event->name->value);
        static::assertSame(CombatState::Idle, $event->state);
        static::assertSame('lorekeeper03 is idle', $event->summary());
        static::assertNull($event->health);
        static::assertNull($event->mana);
    }

    #[Test]
    public function it_reads_a_dead_player_coming_back_without_being_asked(): void
    {
        $event = PlayerStateUpdatedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"lorekeeper03","o":{"intState":1,"intHP":1193,"intMP":100,"intSP":100,"strFrame":"Enter","strPad":"Spawn"}}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(CombatState::Idle, $event->state);
        static::assertSame(1193, $event->health?->value);
        static::assertSame(100, $event->mana?->value);
    }

    #[Test]
    public function it_ignores_updates_that_say_nothing_about_the_state(): void
    {
        $vitals = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"lorekeeper03","o":{"intHPMax":1193,"intMPMax":100,"intHP":990,"intMP":95}}}}',
        )->unwrap();

        static::assertTrue(PlayerStateUpdatedEvent::from($vitals)->isNone());
        static::assertTrue(
            PlayerStateUpdatedEvent::from(
                DelimitedMessage::from('%xt%uotls%-1%made2903%afk:true%')->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function it_refuses_a_state_it_does_not_know(): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerStateUpdatedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"lorekeeper03","o":{"intState":7}}}}',
            )->unwrap(),
        );
    }
}
