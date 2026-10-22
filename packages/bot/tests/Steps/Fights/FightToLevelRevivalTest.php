<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps\Fights;

use Lorekeeper\Bot\Steps\Fights\FightToLevel;
use Lorekeeper\Bot\Steps\Moves\JoinMap;
use Lorekeeper\Bot\Steps\Pauses\Rest;
use Lorekeeper\Bot\Tests\Steps\InOrder;
use Lorekeeper\Bot\Tests\Steps\Play;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class FightToLevelRevivalTest extends TestCase
{
    #[Test]
    public function a_dead_character_resurrects_and_walks_back_to_the_monsters(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script('oaklore', 'r3', target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]),
            null,
            new ResurrectedEvent(),
            null,
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%oaklore%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
                '%xt%zm%resPlayerTimed%946%122628%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%a4>p:122628%wvz%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
            ],
            Play::sent($steps),
        );
        static::assertTrue($me->player()->vitals->alive());
    }

    #[Test]
    public function standing_again_answers_the_resurrection_as_well(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script('oaklore', 'r3', target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]),
            null,
            new PlayerStateUpdatedEvent(new PlayerName('Hero'), CombatState::Idle),
            null,
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
        ]);

        static::assertContains('%xt%zm%resPlayerTimed%946%122628%', Play::sent($steps));
        static::assertTrue($me->player()->vitals->alive());
    }

    #[Test]
    public function a_character_back_on_its_feet_before_asking_does_not_ask(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script('oaklore', 'r3', target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]),
            new PlayerStateUpdatedEvent(new PlayerName('Hero'), CombatState::Idle),
            null,
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
        ]);

        static::assertNotContains('%xt%zm%resPlayerTimed%946%122628%', Play::sent($steps));
        static::assertTrue($me->player()->vitals->alive());
    }

    #[Test]
    public function a_partner_falling_in_the_same_room_does_not_stop_the_fight(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script('oaklore', 'r3', target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Partner'), CombatState::Dead)]),
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
        ]);

        static::assertNotContains('%xt%zm%resPlayerTimed%946%122628%', Play::sent($steps));
        static::assertTrue($me->player()->vitals->alive());
    }

    /** @param 'Left'|'Top' $pad */
    private static function script(string $map, string $cell, int $target, string $pad = 'Left'): InOrder
    {
        $now = Timestamp::fromParts(2026, 10, 16);

        return new InOrder([
            new JoinMap(new MapName($map)),
            new FightToLevel(new MapSpot(new MapCell($cell), MapPad::{$pad}), null, new PlayerLevel($target)),
            new Rest(),
        ], static function () use (&$now): Timestamp {
            return $now = $now->plus(Duration::seconds(3));
        });
    }
}
