<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Server\Event\Player\PlayerLeftEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class PlayerLeftEventTest extends TestCase
{
    #[Test]
    public function it_reads_who_left_the_room(): void
    {
        $event = PlayerLeftEvent::from(DelimitedMessage::from('%xt%exitArea%-1%75536%hero%')->unwrap())->unwrap();

        static::assertSame(75_536, $event->socketId->value);
        static::assertSame('hero', $event->name->value);
        static::assertSame('hero (socket 75536) left', $event->summary());
    }

    #[Test]
    public function it_ignores_other_messages(): void
    {
        static::assertTrue(
            PlayerLeftEvent::from(DelimitedMessage::from('%xt%uotls%-1%hero%afk:true%')->unwrap())->isNone(),
        );
        static::assertTrue(
            PlayerLeftEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"exitArea"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    #[TestWith(['%xt%exitArea%-1%'])]
    #[TestWith(['%xt%exitArea%-1%abc%hero%'])]
    #[TestWith(['%xt%exitArea%-1%75536%'])]
    public function it_rejects_a_player_it_cannot_read(string $message): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerLeftEvent::from(DelimitedMessage::from($message)->unwrap());
    }
}
