<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Map;

use InvalidArgumentException;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapMonsters;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterAppearance;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapMonstersTest extends TestCase
{
    #[Test]
    public function it_finds_a_monster_by_its_spawn_id(): void
    {
        $grunt = self::spawn(3);
        $monsters = new MapMonsters([self::spawn(1), $grunt]);

        static::assertSame($grunt, $monsters->find(new MonsterSpawnId(3))->unwrap());
        static::assertTrue($monsters->find(new MonsterSpawnId(2))->isNone());
    }

    #[Test]
    public function it_rejects_the_same_spawn_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MapMonsters([self::spawn(1), self::spawn(1)]);
    }

    private static function spawn(int $id): MapMonster
    {
        return new MapMonster(
            new MonsterSpawnId($id),
            new Monster(
                new MonsterName('Frogzard'),
                new MonsterLevel(1),
                MonsterRace::None,
                new MonsterAppearance(new Gamefile('frogzard.swf'), new Linkage('Frogzard')),
            ),
            new MapCell('Enter'),
            new Vitals(new Gauge(new Health(250), new Health(250)), new Gauge(new Mana(100), new Mana(100))),
            CombatState::Idle,
        );
    }
}
