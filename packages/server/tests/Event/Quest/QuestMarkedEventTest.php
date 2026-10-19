<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest;

use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestMarkedEventTest extends TestCase
{
    #[Test]
    public function turning_in_a_daily_marks_it_for_the_day(): void
    {
        $event = QuestMarkedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"setAchievement","field":"id2","index":6,"value":1}}}',
            )->unwrap(),
        )->unwrap();

        static::assertEquals(new QuestMark(QuestPeriod::Day, 2, 6), $event->mark);
        static::assertSame('Marked Day quest 2:6', $event->summary());
    }
}
