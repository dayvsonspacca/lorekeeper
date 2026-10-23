<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests;

use Lorekeeper\Client\Character;
use Lorekeeper\Client\Location;
use Lorekeeper\Core\Game\Asset\Gamefile;
use Lorekeeper\Core\Game\Asset\Linkage;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\CharacterClass\CharacterClass;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassCategory;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Cost\SkillCost;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Core\Player\Hero\Class\HeroSkill;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\Hero\HeroAttributes;
use Lorekeeper\Core\Player\Hero\HeroProgression;
use Lorekeeper\Core\Player\Hero\HeroSettings;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Player\Hero\Progression\LevelProgression;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapMonsters;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapPlayers;
use Lorekeeper\Core\World\Map\MapRoom;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterAppearance;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Psl\DateTime\Duration;
use Psl\Option;

final readonly class Fixture
{
    public static function me(
        int $level = 1,
        string $map = 'battleon',
        int $area = 1,
        ?HeroSettings $settings = null,
    ): Character {
        return new Character(
            new Hero(
                new PlayerSpawnId(122_628),
                new PlayerName('Hero'),
                new PlayerLevel($level),
                new HeroAttributes(self::vitals()),
                new HeroClass(
                    self::mage(),
                    new ClassPoints(0),
                    array_map(static fn(Skill $skill): HeroSkill => new HeroSkill($skill, true), self::mage()->skills),
                ),
                new HeroProgression(new LevelProgression(new Gauge(new Experience(0), new Experience(1000)))),
                new HeroInventory(new Quantity(30), new Quantity(30)),
                $settings ?? new HeroSettings(),
            ),
            new Location(new AreaId($area), self::map($map), new MapSpot(new MapCell('Enter'), MapPad::Spawn)),
            CombatState::Idle,
            false,
        );
    }

    /**
     * @param list<MapMonster> $monsters
     * @param list<MapPlayer> $players
     */
    public static function map(string $name, array $monsters = [], array $players = []): Map
    {
        return new Map(
            new MapName($name),
            new MapRoom(1),
            new Gamefile("{$name}.swf"),
            new MapMonsters($monsters),
            new MapPlayers($players),
        );
    }

    public static function vitals(int $health = 390, int $maxHealth = 390, int $mana = 100, int $maxMana = 100): Vitals
    {
        return new Vitals(
            new Gauge(new Health($health), new Health($maxHealth)),
            new Gauge(new Mana($mana), new Mana($maxMana)),
        );
    }

    public static function skill(
        SkillSlot $slot,
        SkillTarget $target,
        SkillCost $cost,
        Duration $cooldown,
        int $targets = 1,
    ): Skill {
        return new Skill(
            $slot,
            new SkillName($slot->name),
            Option\none(),
            $target,
            $cost,
            $cooldown,
            new Quantity($targets),
            Option\none(),
        );
    }

    public static function mage(): CharacterClass
    {
        return new CharacterClass(
            new ClassName('Mage'),
            ClassCategory::Caster,
            Option\none(),
            [
                self::skill(SkillSlot::AutoAttack, SkillTarget::Enemy, new ManaCost(new Mana(0)), Duration::seconds(2)),
                self::skill(SkillSlot::First, SkillTarget::Enemy, new ManaCost(new Mana(20)), Duration::seconds(6)),
                self::skill(SkillSlot::Fourth, SkillTarget::Self, new ManaCost(new Mana(0)), Duration::seconds(20)),
            ],
            [],
        );
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
     * @param list<MapPlayer> $players
     */
    public static function arrivalOf(string $map, int $area, array $monsters, array $players = []): MovedToMapEvent
    {
        return new MovedToMapEvent(new AreaId($area), self::map($map, $monsters, $players));
    }

    public static function seen(
        int $spawnId,
        string $cell = 'Enter',
        MapPad $pad = MapPad::Spawn,
        CombatState $state = CombatState::Idle,
        string $name = 'Hero',
    ): MapPlayer {
        return new MapPlayer(
            new Player(new PlayerSpawnId($spawnId), new PlayerName($name), new PlayerLevel(1), self::vitals()),
            new MapSpot(new MapCell($cell), $pad),
            $state,
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
