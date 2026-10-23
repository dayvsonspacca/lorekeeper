<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Location;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Client\Tracking\AwayTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\LocationTracking;
use Lorekeeper\Client\Tracking\StateTracking;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArrivalTrackingTest extends TestCase
{
    #[Test]
    public function it_takes_the_area_and_map_it_arrives_in_and_its_own_spot_there(): void
    {
        $spot = new MapSpot(new MapCell('r3'), MapPad::Left);

        $location = LocationTracking::follow(self::location(), self::arrival($spot), self::me());

        static::assertSame(946, $location->area->value);
        static::assertSame('oaklore', $location->map->name->value);
        static::assertSame($spot, $location->spot);
    }

    #[Test]
    public function it_keeps_its_spot_when_the_map_does_not_list_it(): void
    {
        $location = self::location();

        $arrived = LocationTracking::follow($location, Fixture::arrival('oaklore', 946), self::me());

        static::assertSame($location->spot, $arrived->spot);
        static::assertSame('oaklore', $arrived->map->name->value);
    }

    #[Test]
    public function it_takes_its_state_from_where_it_arrives_and_stays_as_away_as_it_was(): void
    {
        $arrival = self::arrival(new MapSpot(new MapCell('r3'), MapPad::Left));

        static::assertSame(CombatState::Fighting, StateTracking::follow(CombatState::Idle, $arrival, self::me()));
        static::assertFalse(AwayTracking::follow(false, $arrival, self::me()));
        static::assertTrue(AwayTracking::follow(true, $arrival, self::me()));
    }

    #[Test]
    public function it_is_dead_when_it_dies_and_idle_when_it_comes_back(): void
    {
        static::assertSame(CombatState::Dead, StateTracking::follow(
            CombatState::Fighting,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]),
            self::me(),
        ));
        static::assertSame(CombatState::Fighting, StateTracking::follow(
            CombatState::Fighting,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Partner'), CombatState::Dead)]),
            self::me(),
        ));
        static::assertSame(CombatState::Idle, StateTracking::follow(
            CombatState::Dead,
            new ResurrectedEvent(),
            self::me(),
        ));
    }

    private static function location(): Location
    {
        return Fixture::me()->location();
    }

    private static function me(): Identity
    {
        return new Identity(new PlayerSpawnId(122_628), new PlayerName('Hero'));
    }

    private static function arrival(MapSpot $spot): MovedToMapEvent
    {
        return Fixture::arrivalOf(
            'oaklore',
            946,
            [],
            [
                Fixture::seen(1, name: 'Someone'),
                new MapPlayer(
                    new Player(
                        new PlayerSpawnId(122_628),
                        new PlayerName('Hero'),
                        new PlayerLevel(50),
                        Fixture::vitals(),
                    ),
                    $spot,
                    CombatState::Fighting,
                ),
            ],
        );
    }
}
