<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Script;

use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Script\LevelUpScript;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\Pad;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMonster;
use Lorekeeper\Server\Event\Map\MonsterPlacement;
use Lorekeeper\Server\Event\Map\MonstersDescribedEvent;
use Lorekeeper\Server\Event\Map\MonstersPlacedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Map\PlayersSeenEvent;
use Lorekeeper\Server\Event\Map\SeenPlayer;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use RuntimeException;

/** @mago-expect lint:too-many-methods */
final class LevelUpScriptTest extends TestCase
{
    #[Test]
    public function a_new_character_levels_up_in_oaklore_killing_what_stands_in_r3(): void
    {
        $me = self::me(level: 1, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 2), $me, [
            self::arrive('oaklore', 946, ['r3' => [4, 1]]),
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
        $me = self::me(level: 1, map: 'battleon');

        [$steps] = Play::run(self::script(target: 2), $me, [
            self::arrive('oaklore', 946, ['r3' => [4, 1]]),
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
        static::assertTrue($me->alive());
    }

    #[Test]
    public function past_level_twenty_it_fights_icewing_grunts_in_icestormarena(): void
    {
        $me = self::me(level: 20, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 21), $me, [
            self::arrive('icestormarena', 732_692, ['r4' => [1, 5], 'r7' => [13, 20], 'r8' => [16, 25]]),
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
        $me = self::me(level: 81, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 82), $me, [
            self::arrive('icestormunder', 5_120, ['r2' => [1, 255]]),
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
        $me = self::me(level: 20, map: 'battleon');
        $grunt = new MonsterId(4001);
        $arrival = [
            self::moved('icestormarena', 732_692),
            new MonstersDescribedEvent([self::monster(4001, 20), self::monster(4999, 9)]),
            new MonstersPlacedEvent([
                new MonsterPlacement(new MonsterSpawnId(13), $grunt, new Cell('r7')),
                new MonsterPlacement(new MonsterSpawnId(50), new MonsterId(4999), new Cell('r7')),
                new MonsterPlacement(new MonsterSpawnId(14), $grunt, new Cell('r7')),
            ]),
        ];

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
        $me = self::me(level: 30, map: 'battleon');

        [$steps, $result] = Play::run(self::script(target: 30), $me, []);

        static::assertSame([], $steps);
        static::assertEquals(new Done('Reached level 30'), $result);
    }

    #[Test]
    public function it_stops_when_a_map_cannot_be_joined(): void
    {
        $me = self::me(level: 1, map: 'battleon');

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

    private static function me(int $level, string $map): Character
    {
        $me = new Character(new PlayerName('lorekeeper03'));
        $me->entered(new InGame(new SocketId(122_628), self::moved($map, 1)));
        $me->hear(new PlayersSeenEvent([self::seen('lorekeeper03', $level)]));
        $me->hear(new SkillsLoadedEvent([
            new Skill(SkillSlot::AutoAttack, SkillTarget::Enemy, new Mana(0), Duration::seconds(2)),
            new Skill(SkillSlot::First, SkillTarget::Enemy, new Mana(20), Duration::seconds(6)),
            new Skill(SkillSlot::Fourth, SkillTarget::Self, new Mana(0), Duration::seconds(20)),
        ], [SkillSlot::AutoAttack, SkillSlot::First, SkillSlot::Fourth]));

        return $me;
    }

    /**
     * @param array<string, array{int, int}> $cells
     *
     * @return non-empty-list<MovedToMapEvent|MonstersDescribedEvent|MonstersPlacedEvent>
     */
    private static function arrive(string $map, int $area, array $cells): array
    {
        $placements = [];
        $monsters = [];
        foreach ($cells as $cell => [$spawn, $level]) {
            $placements[] = new MonsterPlacement(
                new MonsterSpawnId($spawn),
                new MonsterId($spawn + 1000),
                new Cell($cell),
            );
            $monsters[] = self::monster($spawn + 1000, $level);
        }

        return [self::moved($map, $area), new MonstersDescribedEvent($monsters), new MonstersPlacedEvent($placements)];
    }

    private static function moved(string $map, int $area): MovedToMapEvent
    {
        return new MovedToMapEvent(new AreaId($area), new RoomId(1), new MapName($map), new Gamefile("{$map}.swf"));
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

    private static function monster(int $id, int $level): MapMonster
    {
        return new MapMonster(
            new MonsterId($id),
            new MonsterName("Monster {$id}"),
            new MonsterLevel($level),
            Race::None,
            new Gamefile('monster.swf'),
            new Linkage('Monster'),
        );
    }

    private static function seen(string $name, int $level): SeenPlayer
    {
        return new SeenPlayer(
            new PlayerName($name),
            new PlayerLevel($level),
            new Cell('Enter'),
            Pad::Spawn,
            CombatState::Idle,
            new Health(390),
            new Health(390),
            new Mana(100),
            new Mana(100),
            false,
        );
    }
}
