<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Lorekeeper\Client\Character;
use Lorekeeper\Client\Location;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerLevelProgress;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerQuestMarks;
use Lorekeeper\Core\Player\Wallet;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use Psl\Option;

final readonly class Characters
{
    /** @param list<MapMonster> $monsters */
    public static function me(string $name, int $level, string $map, array $monsters = []): Character
    {
        return new Character(
            new SocketId(1),
            new Player(
                new PlayerName($name),
                new PlayerLevelProgress(new PlayerLevel($level), new Gauge(new Experience(0), new Experience(1000))),
                new Vitals(new Gauge(new Health(390), new Health(390)), new Gauge(new Mana(100), new Mana(100))),
                new PlayerClass(
                    new CharacterClass(new ClassName('Mage'), ClassCategory::Caster, Option\none(), [], []),
                    new ClassPoints(0),
                    [],
                ),
                new PlayerInventory(new PlayerItems([]), new Quantity(30)),
                new Wallet(new Gold(0), new AdventureCoins(0)),
                new PlayerQuestMarks(),
            ),
            new Location(
                new AreaId(1),
                new RoomId(1),
                new Map(new MapName($map), new Gamefile("{$map}.swf"), $monsters),
                new MapSpot(new MapCell('Enter'), MapPad::Spawn),
            ),
            CombatState::Idle,
            false,
        );
    }
}
