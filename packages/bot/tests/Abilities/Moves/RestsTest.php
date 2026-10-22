<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Abilities\Moves;

use Generator;
use Lorekeeper\Bot\Abilities\Moves\Rests;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Location;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class RestsTest extends TestCase
{
    #[Test]
    public function a_fighter_goes_back_to_the_entrance_waits_out_the_fight_and_cools_down(): void
    {
        $me = self::character('r10', CombatState::Fighting);

        $steps = self::rest($me, [
            null,
            null,
            new PlayerStateUpdatedEvent(new PlayerName('Hero'), CombatState::Idle),
        ]);

        static::assertCount(4, $steps);
        static::assertInstanceOf(Send::class, $steps[0]);
        static::assertSame('%xt%zm%moveToCell%946%Enter%Spawn%', $steps[0]->command->toMessage());
        static::assertInstanceOf(Pause::class, $steps[1]);
        static::assertInstanceOf(Await::class, $steps[2]);
        static::assertEquals(new Pause(Duration::seconds(5)), $steps[3]);
    }

    #[Test]
    public function someone_resting_at_the_entrance_carries_on_at_once(): void
    {
        static::assertSame([], self::rest(self::character('Enter', CombatState::Idle), []));
    }

    #[Test]
    public function someone_idle_elsewhere_only_walks_back_to_the_entrance(): void
    {
        $steps = self::rest(self::character('r3', CombatState::Idle), [null, null]);

        static::assertCount(2, $steps);
        static::assertInstanceOf(Send::class, $steps[0]);
        static::assertInstanceOf(Pause::class, $steps[1]);
    }

    private static function character(string $cell, CombatState $state): Character
    {
        $me = Fixture::me(area: 946);

        return new Character(
            $me->socketId(),
            $me->player(),
            new Location(
                $me->location()->area,
                $me->location()->room,
                $me->location()->map,
                new MapSpot(new MapCell($cell), $cell === 'Enter' ? MapPad::Spawn : MapPad::Left),
            ),
            $state,
            false,
        );
    }

    /**
     * @param list<null|Event|TimedOut> $answers
     *
     * @return list<Instruction>
     */
    private static function rest(Character $me, array $answers): array
    {
        $rester = new class($me) {
            use Rests;

            public function __construct(
                private readonly Character $me,
            ) {}

            /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
            public function resting(): Generator
            {
                return $this->rest();
            }

            protected function me(): Character
            {
                return $this->me;
            }

            protected function send(Command $command): Send
            {
                return new Send($command);
            }
        };

        $steps = [];
        $resting = $rester->resting();
        while ($resting->valid()) {
            $steps[] = $resting->current();
            $answer = array_shift($answers);
            if ($answer instanceof Event) {
                $me->hear($answer);
            }

            $resting->send($answer);
        }

        return $steps;
    }
}
