<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest;

use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
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

        static::assertEquals(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(70)), $event->quest);
        static::assertSame('Completed Daily quest 70', $event->summary());
    }

    #[Test]
    public function an_achievement_that_is_cleared_marks_nothing(): void
    {
        static::assertTrue(
            QuestMarkedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"setAchievement","field":"id2","index":6,"value":0}}}',
                )->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function it_rejects_an_achievement_without_its_index(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('achievement is invalid');

        QuestMarkedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"setAchievement","field":"id2","value":1}}}')->unwrap(),
        );
    }
}
