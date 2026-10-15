<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Map;

use Lorekeeper\Protocol\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapRequiresQuestEventTest extends TestCase
{
    #[Test]
    public function it_matches_the_notice(): void
    {
        $message = DelimitedMessage::from('%xt%warning%1%That map requires special quest actions.%')->unwrap();

        static::assertTrue(MapRequiresQuestEvent::from($message)->isSome());
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
