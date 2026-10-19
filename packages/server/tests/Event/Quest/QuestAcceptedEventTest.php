<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest;

use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestAcceptedEventTest extends TestCase
{
    #[Test]
    public function it_reads_the_quest_the_server_accepted(): void
    {
        $event = QuestAcceptedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"acceptQuest","bSuccess":1,"QuestID":6187,"msg":"success"}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(6187, $event->quest->value);
        static::assertSame('Accepted quest 6187', $event->summary());
    }

    #[Test]
    public function it_says_nothing_when_the_quest_was_not_accepted(): void
    {
        static::assertTrue(
            QuestAcceptedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"acceptQuest","bSuccess":0,"QuestID":6187}}}',
                )->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function it_rejects_an_answer_without_its_quest(): void
    {
        $this->expectException(MalformedEventException::class);

        QuestAcceptedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"acceptQuest","bSuccess":1}}}')->unwrap(),
        );
    }
}
