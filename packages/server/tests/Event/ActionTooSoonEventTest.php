<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event;

use Lorekeeper\Server\Event\ActionTooSoonEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActionTooSoonEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Please slow down. Last action was too soon!%')->unwrap();

        static::assertTrue(ActionTooSoonEvent::from($message)->isSome());
    }

    #[Test]
    public function it_matches_regardless_of_case(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%PLEASE SLOW DOWN. LAST ACTION WAS TOO SOON!%')->unwrap();

        static::assertTrue(ActionTooSoonEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_notices(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Some other notice.%')->unwrap();

        static::assertTrue(ActionTooSoonEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_channels(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%Please slow down. Last action was too soon!%')->unwrap();

        static::assertTrue(ActionTooSoonEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "warning"}}}')->unwrap();

        static::assertTrue(ActionTooSoonEvent::from($message)->isNone());
    }
}
