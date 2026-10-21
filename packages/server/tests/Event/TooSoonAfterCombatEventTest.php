<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event;

use Lorekeeper\Server\Event\ActionTooSoonEvent;
use Lorekeeper\Server\Event\TooSoonAfterCombatEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TooSoonAfterCombatEventTest extends TestCase
{
    #[Test]
    public function it_reads_the_server_refusing_something_right_after_a_fight(): void
    {
        $message = DelimitedMessage::from('%xt%warning%-1%You cannot do that too soon after combat.%')->unwrap();

        static::assertSame('Too soon after combat', TooSoonAfterCombatEvent::from($message)->unwrap()->summary());
        static::assertTrue(ActionTooSoonEvent::from($message)->isNone());
    }

    #[Test]
    public function it_leaves_the_plain_too_soon_alone(): void
    {
        $message = DelimitedMessage::from('%xt%warning%-1%Please slow down. Last action was too soon!%')->unwrap();

        static::assertTrue(TooSoonAfterCombatEvent::from($message)->isNone());
    }
}
