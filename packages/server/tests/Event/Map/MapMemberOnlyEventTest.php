<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapMemberOnlyEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%battleon is an Membership-Only Map.%')->unwrap();

        static::assertTrue(MapMemberOnlyEvent::from($message)->isSome());
    }

    #[Test]
    public function it_carries_the_map_name(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%battleon is an Membership-Only Map.%')->unwrap();

        static::assertSame(
            'battleon',
            MapMemberOnlyEvent::from($message)
                ->unwrap()
                ->mapName
                ->value,
        );
        static::assertSame('battleon is member-only', MapMemberOnlyEvent::from($message)->unwrap()->summary());
    }

    #[Test]
    public function it_matches_regardless_of_case(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%BATTLEON IS AN MEMBERSHIP-ONLY MAP.%')->unwrap();

        static::assertTrue(MapMemberOnlyEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_notices(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Some other notice.%')->unwrap();

        static::assertTrue(MapMemberOnlyEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_channels(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%battleon is an Membership-Only Map.%')->unwrap();

        static::assertTrue(MapMemberOnlyEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "warning"}}}')->unwrap();

        static::assertTrue(MapMemberOnlyEvent::from($message)->isNone());
    }
}
