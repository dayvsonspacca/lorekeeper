<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Combat;

use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CombatRoundEventTest extends TestCase
{
    #[Test]
    public function a_combat_round_tells_who_is_fighting(): void
    {
        $fighting = PlayersStateChangedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"ct","m":{"21":{"intState":2,"intHP":8691}},"p":{"fahry123":{"intState":2}}}}}',
            )->unwrap(),
        )->unwrap();

        static::assertEquals([new PlayerState(new PlayerName('fahry123'), CombatState::Fighting)], $fighting->players);
    }

    #[Test]
    public function it_rejects_a_combat_round_with_a_state_it_does_not_know(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('combat round is invalid');

        PlayersStateChangedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"ct","m":{"21":{"intState":3,"intHP":8691}},"p":{"fahry123":{"intState":2}}}}}',
            )->unwrap(),
        );
    }
}
