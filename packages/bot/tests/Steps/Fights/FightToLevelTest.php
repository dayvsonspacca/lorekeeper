<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps\Fights;

use Lorekeeper\Bot\Steps\Fights\FightToLevel;
use Lorekeeper\Bot\Steps\Moves\JoinMap;
use Lorekeeper\Bot\Steps\Pauses\Rest;
use Lorekeeper\Bot\Tests\Steps\InOrder;
use Lorekeeper\Bot\Tests\Steps\Play;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\ActionTooSoonEvent;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final class FightToLevelTest extends TestCase
{
    #[Test]
    public function a_new_character_levels_up_in_oaklore_killing_what_stands_in_r3(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps, $result] = Play::run(self::script('oaklore', 'r3', target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new MonstersDiedEvent([new MonsterSpawnId(4)]),
            new MonsterRespawnedEvent(new MonsterSpawnId(4)),
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%oaklore%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
            ],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function past_level_twenty_it_fights_icewing_grunts_in_icestormarena(): void
    {
        $me = Fixture::me(level: 20, map: 'battleon');

        [$steps, $result] = Play::run(self::script('icestormarena', 'r7', target: 21), $me, [
            Fixture::arrival('icestormarena', 732_692, ['r4' => [1, 5], 'r7' => [13, 20], 'r8' => [16, 25]]),
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(21), new Experience(9000)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%icestormarena%',
                '%xt%zm%moveToCell%732692%r7%Left%',
                '%xt%zm%gar%732692%0%a1>m:13%wvz%',
            ],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function past_level_seventy_five_it_fights_frost_spirits_in_icestormunder(): void
    {
        $me = Fixture::me(level: 81, map: 'battleon');

        [$steps, $result] = Play::run(self::script('icestormunder', 'r2', target: 82, pad: 'Top'), $me, [
            Fixture::arrival('icestormunder', 5_120, ['r2' => [1, 255]]),
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(82), new Experience(9000)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%icestormunder%',
                '%xt%zm%moveToCell%5120%r2%Top%',
                '%xt%zm%gar%5120%0%a1>m:1%wvz%',
            ],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function when_its_target_dies_it_fights_another_of_the_same_kind_and_never_another_kind(): void
    {
        $me = Fixture::me(level: 20, map: 'battleon');
        $grunt = Fixture::kind(20, 'Icewing Grunt');
        $arrival = Fixture::arrivalOf('icestormarena', 732_692, [
            Fixture::spawn(13, 'r7', $grunt),
            Fixture::spawn(50, 'r7', Fixture::kind(9, 'Frost Spirit')),
            Fixture::spawn(14, 'r7', $grunt),
        ]);

        [$steps] = Play::run(self::script('icestormarena', 'r7', target: 21), $me, [
            $arrival,
            null,
            null,
            null,
            new MonstersDiedEvent([new MonsterSpawnId(13)]),
            null,
            null,
            new MonstersDiedEvent([new MonsterSpawnId(14)]),
            new MonsterRespawnedEvent(new MonsterSpawnId(13)),
            null,
            new LeveledUpEvent(new PlayerLevel(21), new Experience(9000)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%icestormarena%',
                '%xt%zm%moveToCell%732692%r7%Left%',
                '%xt%zm%gar%732692%0%a1>m:13%wvz%',
                '%xt%zm%gar%732692%0%a4>p:122628%wvz%',
                '%xt%zm%gar%732692%0%a1>m:14%wvz%',
                '%xt%zm%gar%732692%0%aa>m:13%wvz%',
            ],
            Play::sent($steps),
        );
    }

    #[Test]
    public function a_character_already_at_the_target_does_nothing(): void
    {
        $me = Fixture::me(level: 30, map: 'battleon');

        [$steps, $result] = Play::run(self::script('icestormarena', 'r7', target: 30), $me, [
            Fixture::arrival('icestormarena', 732_692, ['r7' => [13, 20]]),
        ]);

        static::assertSame(['%xt%zm%cmd%1%tfer%Hero%icestormarena%'], Play::sent($steps));
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function it_stops_when_a_map_cannot_be_joined(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [, $result] = Play::run(self::script('oaklore', 'r3', target: 2), $me, [new MapLockedEvent()]);

        static::assertEquals(new Failed('step 1 (Join oaklore): Could not join oaklore: The map is locked'), $result);
    }

    #[Test]
    public function past_level_ten_it_fights_at_the_gates_of_swordhavenundead_and_waits_when_told_it_was_too_soon(): void
    {
        $me = Fixture::me(level: 10, map: 'battleon');

        [$steps, $result] = Play::run(self::script('swordhavenundead', 'Gates', target: 11), $me, [
            Fixture::arrival('swordhavenundead', 2_046, ['Gates' => [1, 12]]),
            null,
            null,
            null,
            ActionTooSoonEvent::from(
                DelimitedMessage::from('%xt%warning%-1%Please slow down. Last action was too soon!%')->unwrap(),
            )->unwrap(),
            new LeveledUpEvent(new PlayerLevel(11), new Experience(2000)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%swordhavenundead%',
                '%xt%zm%moveToCell%2046%Gates%Left%',
                '%xt%zm%gar%2046%0%a1>m:1%wvz%',
            ],
            array_slice(Play::sent($steps), offset: 0, length: 3),
        );
        static::assertEquals(new Done('all steps are done'), $result);
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
