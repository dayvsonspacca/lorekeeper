<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Map;

use InvalidArgumentException;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapPlayers;
use Lorekeeper\Core\World\Map\MapSpot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapPlayersTest extends TestCase
{
    #[Test]
    public function it_finds_a_player_by_its_spawn_id(): void
    {
        $hero = self::seen(45_466, 'Hero');
        $players = new MapPlayers([self::seen(45_467, 'Ally'), $hero]);

        static::assertSame($hero, $players->find(new PlayerSpawnId(45_466))->unwrap());
        static::assertTrue($players->find(new PlayerSpawnId(1))->isNone());
    }

    #[Test]
    public function it_rejects_the_same_player_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MapPlayers([self::seen(45_466, 'Hero'), self::seen(45_466, 'Hero')]);
    }

    private static function seen(int $id, string $name): MapPlayer
    {
        return new MapPlayer(
            new Player(
                new PlayerSpawnId($id),
                new PlayerName($name),
                new PlayerLevel(81),
                new Vitals(new Gauge(new Health(2585), new Health(2585)), new Gauge(new Mana(100), new Mana(100))),
            ),
            new MapSpot(new MapCell('Enter'), MapPad::Spawn),
            CombatState::Idle,
            false,
        );
    }
}
