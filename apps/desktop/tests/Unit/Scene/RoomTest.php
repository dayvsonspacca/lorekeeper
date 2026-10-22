<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scene;

use Lorekeeper\Apps\Desktop\Bots\Scene\Room;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterAppearance;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\MonsterHealth;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Map\RoomPlayer;
use Lorekeeper\Server\Event\Player\PlayerEnteredEvent;
use Lorekeeper\Server\Event\Player\PlayerLeftEvent;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RoomTest extends TestCase
{
    #[Test]
    public function it_follows_how_the_monsters_fight_die_and_come_back(): void
    {
        $me = new PlayerName('Hero');
        $room = Room::of(self::map());

        $room = $room->heard(
            new MonstersHealthChangedEvent([new MonsterHealth(new MonsterSpawnId(1), new Health(120))]),
            $me,
            now: 100,
        );
        static::assertSame(120, $room->monster(new MonsterSpawnId(1))?->vitals->health->current->value);
        static::assertSame(CombatState::Fighting, $room->monster(new MonsterSpawnId(1))?->state);
        static::assertSame([1 => 100], $room->struck);

        $room = $room->heard(new MonstersDiedEvent([new MonsterSpawnId(1)]), $me, now: 104);
        static::assertSame(0, $room->monster(new MonsterSpawnId(1))?->vitals->health->current->value);
        static::assertSame(CombatState::Dead, $room->monster(new MonsterSpawnId(1))?->state);

        $room = $room->heard(new MonsterRespawnedEvent(new MonsterSpawnId(1)), $me, now: 110);
        static::assertSame(260, $room->monster(new MonsterSpawnId(1))?->vitals->health->current->value);
        static::assertSame(CombatState::Idle, $room->monster(new MonsterSpawnId(1))?->state);
        static::assertSame([], $room->struck);
    }

    #[Test]
    public function it_never_lets_a_monster_go_above_its_maximum(): void
    {
        $room = Room::of(self::map())->heard(
            new MonstersHealthChangedEvent([
                new MonsterHealth(new MonsterSpawnId(2), new Health(9999)),
                new MonsterHealth(new MonsterSpawnId(77), new Health(10)),
            ]),
            new PlayerName('Hero'),
            now: 100,
        );

        static::assertSame(260, $room->monster(new MonsterSpawnId(2))?->vitals->health->current->value);
        static::assertNull($room->monster(new MonsterSpawnId(77)));
    }

    #[Test]
    public function it_keeps_the_other_players_in_the_room(): void
    {
        $me = new PlayerName('Hero');
        $arrival = new MovedToMapEvent(new AreaId(7), new RoomId(3), self::map(), [
            self::seen(1, 'Hero'),
            self::seen(2, 'Artix'),
        ]);

        $room = new Room()->heard($arrival, $me, now: 1);
        static::assertCount(2, $room->monsters);
        static::assertSame(['Artix'], $room->players);

        $room = $room
            ->heard(new PlayerEnteredEvent(new SocketId(3), new PlayerName('Cysero')), $me, now: 2)
            ->heard(new PlayerEnteredEvent(new SocketId(2), new PlayerName('artix')), $me, now: 3)
            ->heard(new PlayerLeftEvent(new SocketId(2), new PlayerName('Artix')), $me, now: 4);

        static::assertSame(['Cysero'], $room->players);
    }

    private static function map(): Map
    {
        return new Map(new MapName('oaklore'), new Gamefile('Oaklore/town-oaklore.swf'), [
            self::monster(1, 'Bonemuncher'),
            self::monster(2, 'Bonemuncher'),
        ]);
    }

    private static function monster(int $id, string $name): MapMonster
    {
        return new MapMonster(
            new MonsterSpawnId($id),
            new Monster(
                new MonsterName($name),
                new MonsterLevel(4),
                MonsterRace::Undead,
                new MonsterAppearance(new Gamefile('mon/bonemuncher.swf'), new Linkage('Bonemuncher')),
            ),
            new MapCell('r3'),
            new Vitals(new Gauge(new Health(260), new Health(260)), new Gauge(new Mana(0), new Mana(0))),
            CombatState::Idle,
        );
    }

    private static function seen(int $socket, string $name): RoomPlayer
    {
        return new RoomPlayer(
            new SocketId($socket),
            new MapPlayer(
                new PlayerName($name),
                new PlayerLevel(30),
                new Vitals(new Gauge(new Health(900), new Health(900)), new Gauge(new Mana(100), new Mana(100))),
                new MapSpot(new MapCell('Enter'), MapPad::Spawn),
                CombatState::Idle,
                false,
            ),
        );
    }
}
