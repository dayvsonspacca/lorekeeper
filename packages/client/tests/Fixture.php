<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Character;
use Lorekeeper\Client\Location;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterAppearance;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Map\RoomPlayer;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use Psl\DateTime\Duration;
use Psl\Option;

final readonly class Fixture
{
    public static function me(int $level = 1, string $map = 'battleon', int $area = 1): Character
    {
        return new Character(
            new SocketId(122_628),
            new Player(
                new PlayerName('lorekeeper03'),
                new PlayerLevel($level),
                self::vitals(),
                new PlayerClass(self::mage(), new ClassPoints(0)),
                new PlayerInventory([]),
            ),
            new Location(
                new AreaId($area),
                new Map(new MapName($map), new Gamefile("{$map}.swf"), []),
                new MapSpot(new MapCell('Enter'), MapPad::Spawn),
            ),
            CombatState::Idle,
            false,
            [SkillSlot::AutoAttack, SkillSlot::First, SkillSlot::Fourth],
        );
    }

    public static function vitals(int $health = 390, int $maxHealth = 390, int $mana = 100, int $maxMana = 100): Vitals
    {
        return new Vitals(
            new Gauge(new Health($health), new Health($maxHealth)),
            new Gauge(new Mana($mana), new Mana($maxMana)),
        );
    }

    public static function mage(): CharacterClass
    {
        return new CharacterClass(new ClassName('Mage'), ClassCategory::Caster, Option\none(), [
            new Skill(SkillSlot::AutoAttack, SkillTarget::Enemy, new Mana(0), Duration::seconds(2)),
            new Skill(SkillSlot::First, SkillTarget::Enemy, new Mana(20), Duration::seconds(6)),
            new Skill(SkillSlot::Fourth, SkillTarget::Self, new Mana(0), Duration::seconds(20)),
        ]);
    }

    /** @param array<string, array{int, int}> $cells */
    public static function arrival(string $map, int $area, array $cells = []): MovedToMapEvent
    {
        $monsters = [];
        foreach ($cells as $cell => [$spawn, $level]) {
            $monsters[] = self::spawn($spawn, $cell, self::kind($level));
        }

        return self::arrivalOf($map, $area, $monsters);
    }

    /**
     * @param list<MapMonster> $monsters
     * @param list<RoomPlayer> $players
     */
    public static function arrivalOf(string $map, int $area, array $monsters, array $players = []): MovedToMapEvent
    {
        return new MovedToMapEvent(
            new AreaId($area),
            new RoomId(1),
            new Map(new MapName($map), new Gamefile("{$map}.swf"), $monsters),
            $players,
        );
    }

    public static function kind(int $level, string $name = 'Monster'): Monster
    {
        return new Monster(
            new MonsterName($name),
            new MonsterLevel($level),
            MonsterRace::None,
            new MonsterAppearance(new Gamefile('monster.swf'), new Linkage('Monster')),
        );
    }

    public static function spawn(int $id, string $cell, Monster $kind): MapMonster
    {
        return new MapMonster(
            new MonsterSpawnId($id),
            $kind,
            new MapCell($cell),
            self::vitals(health: 610, maxHealth: 610),
            CombatState::Idle,
        );
    }
}
