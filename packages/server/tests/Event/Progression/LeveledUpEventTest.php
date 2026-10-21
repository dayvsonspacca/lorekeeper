<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Progression;

use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeveledUpEventTest extends TestCase
{
    #[Test]
    public function it_reads_the_new_level_and_what_the_next_one_takes(): void
    {
        $event = LeveledUpEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"levelUp","xp":5,"intExpToLevel":100,"intLevel":2}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(2, $event->level->value);
        static::assertSame(100, $event->toNextLevel->value);
        static::assertSame('Reached level 2', $event->summary());
    }

    #[Test]
    public function it_rejects_a_level_out_of_the_game(): void
    {
        $this->expectException(MalformedEventException::class);

        LeveledUpEvent::from(
            JsonMessage::from('{"b":{"o":{"cmd":"levelUp","intLevel":101,"intExpToLevel":0}}}')->unwrap(),
        );
    }
}
