<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapLockedEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%The map is locked.%')->unwrap();

        static::assertTrue(MapLockedEvent::from($message)->isSome());
    }

    #[Test]
    public function it_matches_regardless_of_case(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%THE MAP IS LOCKED.%')->unwrap();

        static::assertTrue(MapLockedEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_notices(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Some other notice.%')->unwrap();

        static::assertTrue(MapLockedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_channels(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%The map is locked.%')->unwrap();

        static::assertTrue(MapLockedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "warning"}}}')->unwrap();

        static::assertTrue(MapLockedEvent::from($message)->isNone());
    }
}
