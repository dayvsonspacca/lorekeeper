<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Reward;

use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MonsterRewardedEventTest extends TestCase
{
    #[Test]
    public function it_reads_what_killing_a_monster_gave(): void
    {
        $event = MonsterRewardedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addGoldExp","intGold":3,"intExp":10,"iCP":510,"bonusCP":255,"typ":"m","id":4}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(3, $event->gold->value);
        static::assertSame(10, $event->experience->value);
        static::assertSame(510, $event->classPoints->value);
        static::assertSame(4, $event->monster->value);
        static::assertSame('Earned 10 experience and 3 gold from monster 4', $event->summary());
    }

    #[Test]
    public function it_ignores_rewards_from_anything_else(): void
    {
        static::assertTrue(
            MonsterRewardedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"addGoldExp","intGold":3,"intExp":10,"typ":"q","id":178}}}',
                )->unwrap(),
            )->isNone(),
        );
        static::assertTrue(
            MonsterRewardedEvent::from(
                JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"levelUp","intLevel":2}}}')->unwrap(),
            )->isNone(),
        );
    }

    #[Test]
    public function it_rejects_a_reward_that_is_not_an_amount(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('reward is invalid');

        MonsterRewardedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addGoldExp","intGold":-3,"intExp":10,"iCP":510,"bonusCP":255,"typ":"m","id":4}}}',
            )->unwrap(),
        );
    }

    #[Test]
    public function it_rejects_a_reward_without_the_monster(): void
    {
        $this->expectException(MalformedEventException::class);

        MonsterRewardedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addGoldExp","intGold":3,"intExp":10,"typ":"m"}}}',
            )->unwrap(),
        );
    }
}
