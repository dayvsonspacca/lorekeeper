<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Map;

use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapUnavailable;
use Lorekeeper\Protocol\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
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
    }

    #[Test]
    public function it_records_the_map_fact(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%battleon is not available.%')->unwrap();

        static::assertEquals(
            [new Fact(new MapName('battleon'), new MapUnavailable())],
            MapNotAvailableEvent::from($message)->unwrap()->facts(),
        );
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
