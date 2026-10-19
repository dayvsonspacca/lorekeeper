<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Player;

use Lorekeeper\Server\Event\Player\PlayerUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerVitalsUpdatedEventTest extends TestCase
{
    private const string AFTER_LOGIN = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"made2903","o":{"intHPMax":2585,"intMPMax":100,"intHP":2585,"intMP":100}}}}
        JSON;

    #[Test]
    public function it_reads_a_players_health_and_mana_sent_after_login(): void
    {
        $event = PlayerVitalsUpdatedEvent::from(JsonMessage::from(self::AFTER_LOGIN)->unwrap())->unwrap();

        static::assertInstanceOf(PlayerUpdatedEvent::class, $event);
        static::assertSame('made2903', $event->name->value);
        static::assertSame([2585, 2585, 100, 100], [
            $event->vitals->health->current->value,
            $event->vitals->health->maximum->value,
            $event->vitals->mana->current->value,
            $event->vitals->mana->maximum->value,
        ]);
        static::assertSame('made2903 has 2585/2585 health and 100/100 mana', $event->summary());
    }

    #[Test]
    public function it_ignores_updates_without_all_of_health_and_mana(): void
    {
        $moved = DelimitedMessage::from('%xt%uotls%-1%hero%strFrame:Enter,strPad:Spawn%')->unwrap();
        $hit = JsonMessage::from('{"b":{"o":{"cmd":"uotls","unm":"hero","o":{"intHP":100,"intMP":50}}}}')->unwrap();

        static::assertTrue(PlayerVitalsUpdatedEvent::from($moved)->isNone());
        static::assertTrue(PlayerVitalsUpdatedEvent::from($hit)->isNone());
        static::assertTrue(
            PlayerVitalsUpdatedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"ct"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_health_above_its_maximum(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('health or mana is above its maximum');

        PlayerVitalsUpdatedEvent::from(
            JsonMessage::from(
                '{"b":{"o":{"cmd":"uotls","unm":"hero","o":{"intHPMax":100,"intMPMax":100,"intHP":101,"intMP":100}}}}',
            )->unwrap(),
        );
    }
}
