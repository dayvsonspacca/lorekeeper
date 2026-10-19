<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot;

use Lorekeeper\Client\Bot\LevelUpScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use RuntimeException;

final class LevelUpScriptTest extends TestCase
{
    #[Test]
    public function a_new_character_levels_up_in_oaklore_killing_what_stands_in_r3(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new MonstersDiedEvent([new MonsterSpawnId(4)]),
            new MonsterRespawnedEvent(new MonsterSpawnId(4)),
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
            null,
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%oaklore%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
            ],
            self::sent($steps),
        );
        static::assertEquals(new Progress(1, 1), $steps[array_key_last($steps)]);
        static::assertEquals(new Done('Reached level 2'), $result);
    }

    #[Test]
    public function a_dead_character_resurrects_and_walks_back_to_the_monsters(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script(target: 2), $me, [
            Fixture::arrival('oaklore', 946, ['r3' => [4, 1]]),
            null,
            null,
            null,
            new PlayerDiedEvent(),
            null,
            new ResurrectedEvent(),
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(2), new Experience(200)),
            null,
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%oaklore%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%a1>m:4%wvz%',
                '%xt%zm%resPlayerTimed%946%122628%',
                '%xt%zm%moveToCell%946%r3%Left%',
                '%xt%zm%gar%946%0%aa>m:4%wvz%',
            ],
            self::sent($steps),
        );
        static::assertTrue($me->player()->vitals->alive());
    }

    #[Test]
    public function past_level_twenty_it_fights_icewing_grunts_in_icestormarena(): void
    {
        $me = Fixture::me(level: 20, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 21), $me, [
            Fixture::arrival('icestormarena', 732_692, ['r4' => [1, 5], 'r7' => [13, 20], 'r8' => [16, 25]]),
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(21), new Experience(9000)),
            null,
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%icestormarena%',
                '%xt%zm%moveToCell%732692%r7%Left%',
                '%xt%zm%gar%732692%0%a1>m:13%wvz%',
            ],
            self::sent($steps),
        );
        static::assertEquals(new Done('Reached level 21'), $result);
    }

    #[Test]
    public function past_level_seventy_five_it_fights_frost_spirits_in_icestormunder(): void
    {
        $me = Fixture::me(level: 81, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 82), $me, [
            Fixture::arrival('icestormunder', 5_120, ['r2' => [1, 255]]),
            null,
            null,
            null,
            new LeveledUpEvent(new PlayerLevel(82), new Experience(9000)),
            null,
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%icestormunder%',
                '%xt%zm%moveToCell%5120%r2%Top%',
                '%xt%zm%gar%5120%0%a1>m:1%wvz%',
            ],
            self::sent($steps),
        );
        static::assertEquals(new Done('Reached level 82'), $result);
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

        [$steps] = Play::run(self::script(target: 21), $me, [
            $arrival,
            null,
            null,
            null,
            new MonstersDiedEvent([new MonsterSpawnId(13)]),
            null,
            new MonstersDiedEvent([new MonsterSpawnId(14)]),
            new MonsterRespawnedEvent(new MonsterSpawnId(13)),
            null,
            new LeveledUpEvent(new PlayerLevel(21), new Experience(9000)),
            null,
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%icestormarena%',
                '%xt%zm%moveToCell%732692%r7%Left%',
                '%xt%zm%gar%732692%0%a1>m:13%wvz%',
                '%xt%zm%gar%732692%0%aa>m:14%wvz%',
                '%xt%zm%gar%732692%0%a1>m:13%wvz%',
            ],
            self::sent($steps),
        );
    }

    #[Test]
    public function a_character_already_at_the_target_does_nothing(): void
    {
        $me = Fixture::me(level: 30, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 30), $me, []);

        static::assertSame([], $steps);
        static::assertEquals(new Done('Reached level 30'), $result);
    }

    #[Test]
    public function it_stops_when_a_map_cannot_be_joined(): void
    {
        $me = Fixture::me(level: 1, map: 'battleon');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not join oaklore: The map is locked');

        Play::run(self::script(target: 2), $me, [new MapLockedEvent()]);
    }

    private static function script(int $target): LevelUpScript
    {
        $now = Timestamp::fromParts(2026, 10, 16);

        return new LevelUpScript(new PlayerLevel($target), static function () use (&$now): Timestamp {
            return $now = $now->plus(Duration::seconds(3));
        });
    }

    /**
     * @param list<Instruction> $steps
     *
     * @return list<string>
     */
    private static function sent(array $steps): array
    {
        return array_values(array_map(
            static fn(Send $send): string => $send->command->toMessage(),
            array_filter($steps, static fn(Instruction $step): bool => $step instanceof Send),
        ));
    }
}
