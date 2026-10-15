<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event;

use Lorekeeper\Protocol\Event\AwayFromKeyboardEvent;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AwayFromKeyboardEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%You have been flagged as away from keyboard.%')->unwrap();

        static::assertTrue(AwayFromKeyboardEvent::from($message)->isSome());
    }

    #[Test]
    public function it_matches_regardless_of_case(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%YOU HAVE BEEN FLAGGED AS AWAY FROM KEYBOARD.%')->unwrap();

        static::assertTrue(AwayFromKeyboardEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_notices(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%Some other notice.%')->unwrap();

        static::assertTrue(AwayFromKeyboardEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_channels(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%You have been flagged as away from keyboard.%')->unwrap();

        static::assertTrue(AwayFromKeyboardEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "warning"}}}')->unwrap();

        static::assertTrue(AwayFromKeyboardEvent::from($message)->isNone());
    }
}
