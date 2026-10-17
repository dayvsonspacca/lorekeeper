<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot;

use Lorekeeper\Client\Bot\MapCell;
use Lorekeeper\Client\Bot\MapLayout;
use Lorekeeper\Client\Bot\Spawn;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Server\Event\Map\MapMonster;
use Lorekeeper\Server\Event\Map\MonsterPlacement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapLayoutTest extends TestCase
{
    #[Test]
    public function it_lays_the_map_out_cell_by_cell_with_each_monster_described(): void
    {
        $mage = self::monster(3984, 'Skeletal Ice Mage', 5);
        $elemental = self::monster(3985, 'Ice Elemental', 10);
        $layout = new MapLayout()->placed([
            new MonsterPlacement(new MonsterSpawnId(1), $mage->id, new Cell('r4')),
            new MonsterPlacement(new MonsterSpawnId(5), $elemental->id, new Cell('r5')),
            new MonsterPlacement(new MonsterSpawnId(2), $mage->id, new Cell('r4')),
            new MonsterPlacement(new MonsterSpawnId(9), new MonsterId(9999), new Cell('r6')),
        ])->described([$mage, $elemental]);

        static::assertEquals(
            [
                new MapCell(new Cell('r4'), [
                    new Spawn(new MonsterSpawnId(1), $mage),
                    new Spawn(new MonsterSpawnId(2), $mage),
                ]),
                new MapCell(new Cell('r5'), [new Spawn(new MonsterSpawnId(5), $elemental)]),
            ],
            $layout->cells(),
        );
        static::assertSame([], new MapLayout()->cells());
    }

    private static function monster(int $id, string $name, int $level): MapMonster
    {
        return new MapMonster(
            new MonsterId($id),
            new MonsterName($name),
            new MonsterLevel($level),
            Race::Undead,
            new Gamefile('monster.swf'),
            new Linkage('Monster'),
        );
    }
}
