<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Map;

use Lorekeeper\Protocol\Event\Map\AlreadyInMapEvent;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AlreadyInMapEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Cannot join a room you are already in.%')->unwrap();

        static::assertTrue(AlreadyInMapEvent::from($message)->isSome());
    }

    #[Test]
    public function it_matches_regardless_of_case(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%CANNOT JOIN A ROOM YOU ARE ALREADY IN.%')->unwrap();

        static::assertTrue(AlreadyInMapEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_notices(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Some other notice.%')->unwrap();

        static::assertTrue(AlreadyInMapEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_channels(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%Cannot join a room you are already in.%')->unwrap();

        static::assertTrue(AlreadyInMapEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "warning"}}}')->unwrap();

        static::assertTrue(AlreadyInMapEvent::from($message)->isNone());
    }
}
