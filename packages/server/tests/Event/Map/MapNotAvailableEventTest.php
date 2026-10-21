<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapNotAvailableEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%battleon is not available.%')->unwrap();

        static::assertTrue(MapNotAvailableEvent::from($message)->isSome());
    }

    #[Test]
    public function it_carries_the_map_name(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%battleon is not available.%')->unwrap();

        static::assertSame(
            'battleon',
            MapNotAvailableEvent::from($message)
                ->unwrap()
                ->mapName
                ->value,
        );
        static::assertSame('battleon is not available', MapNotAvailableEvent::from($message)->unwrap()->summary());
    }

    #[Test]
    public function it_matches_regardless_of_case(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%BATTLEON IS NOT AVAILABLE.%')->unwrap();

        static::assertTrue(MapNotAvailableEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_notices(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Some other notice.%')->unwrap();

        static::assertTrue(MapNotAvailableEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_channels(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%battleon is not available.%')->unwrap();

        static::assertTrue(MapNotAvailableEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "warning"}}}')->unwrap();

        static::assertTrue(MapNotAvailableEvent::from($message)->isNone());
    }
}
