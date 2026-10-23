<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Lorekeeper\Client\Character;
use Lorekeeper\Client\Location;
use Lorekeeper\Core\Game\Asset\Gamefile;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Game\Setting\GameSetting;
use Lorekeeper\Core\Item\Equipment\CharacterClass\CharacterClass;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassCategory;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\Hero\HeroAttributes;
use Lorekeeper\Core\Player\Hero\HeroProgression;
use Lorekeeper\Core\Player\Hero\HeroSettings;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Player\Hero\Progression\LevelProgression;
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
use Lorekeeper\Core\World\Map\MapPlayers;
use Lorekeeper\Core\World\Map\MapRoom;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use Psl\Option;

final readonly class Characters
{
    /**
     * @param list<MapMonster> $monsters
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public static function me(
        string $name,
        int $level,
        string $map,
        array $monsters = [],
        ?HeroClass $class = null,
        ?HeroInventory $inventory = null,
        ?HeroSettings $settings = null,
    ): Character {
        return new Character(
            new Hero(
                new PlayerSpawnId(1),
                new PlayerName($name),
                new PlayerLevel($level),
                new HeroAttributes(
                    new Vitals(new Gauge(new Health(390), new Health(390)), new Gauge(new Mana(100), new Mana(100))),
                ),
                $class ?? new HeroClass(
                    new CharacterClass(new ClassName('Mage'), ClassCategory::Caster, Option\none(), [], []),
                    new ClassPoints(0),
                    [],
                ),
                new HeroProgression(new LevelProgression(new Gauge(new Experience(0), new Experience(1000)))),
                $inventory ?? new HeroInventory(new Quantity(30), new Quantity(30)),
                $settings ?? new HeroSettings(GameSetting::cases()),
            ),
            new Location(
                new AreaId(1),
                new Map(
                    new MapName($map),
                    new MapRoom(1),
                    new Gamefile("{$map}.swf"),
                    new MapMonsters($monsters),
                    new MapPlayers([]),
                ),
                new MapSpot(new MapCell('Enter'), MapPad::Spawn),
            ),
            CombatState::Idle,
            false,
        );
    }
}
