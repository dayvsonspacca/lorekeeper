<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerUpdatedEventTest extends TestCase
{
    #[Test]
    public function it_reads_the_updated_player_from_delimited_message(): void
    {
        $message = DelimitedMessage::from('%xt%uotls%-1%hero%strFrame:Enter,strPad:Spawn%')->unwrap();

        static::assertSame(
            'hero',
            PlayerUpdatedEvent::from($message)
                ->unwrap()
                ->name
                ->value,
        );
    }

    #[Test]
    public function it_reads_the_updated_player_from_json_message(): void
    {
        $message = JsonMessage::from(
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"hero","o":{"intHPMax":2585}}}}',
        )->unwrap();

        static::assertSame(
            'hero',
            PlayerUpdatedEvent::from($message)
                ->unwrap()
                ->name
                ->value,
        );
    }

    #[Test]
    public function it_ignores_other_messages(): void
    {
        static::assertTrue(
            PlayerUpdatedEvent::from(DelimitedMessage::from('%xt%exitArea%-1%1%hero%')->unwrap())->isNone(),
        );
        static::assertTrue(PlayerUpdatedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"ct"}}}')->unwrap())->isNone());
        static::assertTrue(PlayerUpdatedEvent::from(XmlMessage::from('<cross-domain-policy/>')->unwrap())->isNone());
    }

    #[Test]
    public function it_rejects_delimited_message_without_name(): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerUpdatedEvent::from(DelimitedMessage::from('%xt%uotls%-1%')->unwrap());
    }

    #[Test]
    public function it_rejects_json_message_without_name(): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerUpdatedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"uotls","o":{}}}}')->unwrap());
    }
}
