<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest;

use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestTurnInRefusedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestTurnInAnswerTest extends TestCase
{
    private const string COMPLETED = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"ccqr","bSuccess":1,"QuestID":6187,"sName":"Odd's 'n' Ends","rewardObj":{"intGold":0,"intExp":0,"iCP":0,"typ":"q","intCoins":0}}}}
        JSON;

    private const string REFUSED = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"ccqr","bSuccess":0,"QuestID":6187,"sName":"Odd's 'n' Ends","msg":"Missing Badge"}}}
        JSON;

    #[Test]
    public function a_quest_the_server_took_is_completed(): void
    {
        $event = QuestCompletedEvent::from(JsonMessage::from(self::COMPLETED)->unwrap())->unwrap();

        static::assertSame([6187, "Odd's 'n' Ends"], [$event->quest->value, $event->name->value]);
        static::assertSame("Completed Odd's 'n' Ends", $event->summary());
        static::assertTrue(QuestTurnInRefusedEvent::from(JsonMessage::from(self::COMPLETED)->unwrap())->isNone());
    }

    #[Test]
    public function a_quest_the_server_would_not_take_is_refused_with_its_reason(): void
    {
        $event = QuestTurnInRefusedEvent::from(JsonMessage::from(self::REFUSED)->unwrap())->unwrap();

        static::assertSame([6187, 'Missing Badge'], [$event->quest->value, $event->reason]);
        static::assertSame("Could not complete Odd's 'n' Ends: Missing Badge", $event->summary());
        static::assertTrue(QuestCompletedEvent::from(JsonMessage::from(self::REFUSED)->unwrap())->isNone());
    }

    #[Test]
    public function it_rejects_an_answer_without_its_quest(): void
    {
        $this->expectException(MalformedEventException::class);

        QuestCompletedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"ccqr","bSuccess":1}}}')->unwrap(),
        );
    }
}
