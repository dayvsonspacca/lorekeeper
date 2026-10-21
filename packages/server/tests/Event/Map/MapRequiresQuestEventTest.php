<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Map;

use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapRequiresQuestEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%That map requires special quest actions.%')->unwrap();

        static::assertTrue(MapRequiresQuestEvent::from($message)->isSome());
        static::assertSame(
            'That map requires special quest actions',
            MapRequiresQuestEvent::from($message)->unwrap()->summary(),
        );
    }

    #[Test]
    public function it_matches_regardless_of_case(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%THAT MAP REQUIRES SPECIAL QUEST ACTIONS.%')->unwrap();

        static::assertTrue(MapRequiresQuestEvent::from($message)->isSome());
    }

    #[Test]
    public function it_ignores_other_notices(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%Some other notice.%')->unwrap();

        static::assertTrue(MapRequiresQuestEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_other_channels(): void
    {
        $message = DelimitedMessage::from('%xt%server%1%That map requires special quest actions.%')->unwrap();

        static::assertTrue(MapRequiresQuestEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_json_message(): void
    {
        $message = JsonMessage::from('{"b": {"o": {"cmd": "warning"}}}')->unwrap();

        static::assertTrue(MapRequiresQuestEvent::from($message)->isNone());
    }
}
