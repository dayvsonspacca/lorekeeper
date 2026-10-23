<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\VitalsTracking;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\Event\Combat\PlayerHealth;
use Lorekeeper\Server\Event\Combat\PlayerMana;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** @mago-expect lint:too-many-methods */
final class VitalsTrackingTest extends TestCase
{
    #[Test]
    public function it_takes_the_health_and_mana_the_server_says_are_mine(): void
    {
        $vitals = Fixture::vitals();

        $theirs = VitalsTracking::follow(
            $vitals,
            new PlayerVitalsUpdatedEvent(new PlayerName('Someone'), Fixture::vitals(10, 10, 10, 10)),
            self::me(),
        );
        static::assertFalse($theirs);
        static::assertSame([390, 390, 100, 100], self::values($vitals));

        $mine = VitalsTracking::follow(
            $vitals,
            new PlayerVitalsUpdatedEvent(new PlayerName('HERO'), Fixture::vitals(2585, 2585, 90, 100)),
            self::me(),
        );
        static::assertTrue($mine);
        static::assertSame([2585, 2585, 90, 100], self::values($vitals));
    }

    #[Test]
    public function it_says_nothing_changed_when_the_vitals_are_the_same(): void
    {
        static::assertFalse(VitalsTracking::follow(
            Fixture::vitals(),
            new PlayerVitalsUpdatedEvent(new PlayerName('Hero'), Fixture::vitals()),
            self::me(),
        ));
    }

    #[Test]
    public function a_fight_changes_health_and_mana_but_not_how_high_they_go(): void
    {
        $vitals = Fixture::vitals();

        VitalsTracking::follow(
            $vitals,
            new PlayersHealthChangedEvent([
                new PlayerHealth(new PlayerName('Someone'), new Health(1)),
                new PlayerHealth(new PlayerName('Hero'), new Health(189)),
            ]),
            self::me(),
        );
        VitalsTracking::follow(
            $vitals,
            new PlayersManaChangedEvent([new PlayerMana(new PlayerName('Hero'), new Mana(87))]),
            self::me(),
        );

        static::assertSame([189, 390, 87, 100], self::values($vitals));
    }

    #[Test]
    public function healing_and_regaining_mana_raise_them_back(): void
    {
        $vitals = Fixture::vitals(health: 189, mana: 20);

        VitalsTracking::follow(
            $vitals,
            new PlayersHealthChangedEvent([new PlayerHealth(new PlayerName('Hero'), new Health(300))]),
            self::me(),
        );
        VitalsTracking::follow(
            $vitals,
            new PlayersManaChangedEvent([new PlayerMana(new PlayerName('Hero'), new Mana(64))]),
            self::me(),
        );

        static::assertSame([300, 390, 64, 100], self::values($vitals));
    }

    #[Test]
    public function more_than_the_maximum_it_knew_becomes_the_new_maximum(): void
    {
        $vitals = Fixture::vitals();

        VitalsTracking::follow(
            $vitals,
            new PlayersHealthChangedEvent([new PlayerHealth(new PlayerName('Hero'), new Health(450))]),
            self::me(),
        );
        VitalsTracking::follow(
            $vitals,
            new PlayersManaChangedEvent([new PlayerMana(new PlayerName('Hero'), new Mana(120))]),
            self::me(),
        );

        static::assertSame([450, 450, 120, 120], self::values($vitals));
    }

    #[Test]
    public function dying_leaves_no_health_and_coming_back_fills_health_and_mana(): void
    {
        $vitals = Fixture::vitals(health: 189, mana: 20);

        VitalsTracking::follow(
            $vitals,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]),
            self::me(),
        );
        static::assertFalse($vitals->alive());
        static::assertSame([0, 390, 20, 100], self::values($vitals));

        static::assertTrue(VitalsTracking::follow($vitals, new ResurrectedEvent(), self::me()));
        static::assertSame([390, 390, 100, 100], self::values($vitals));
    }

    #[Test]
    public function standing_again_without_a_resurrection_fills_health_and_mana(): void
    {
        $vitals = Fixture::vitals(health: 189, mana: 20);
        VitalsTracking::follow(
            $vitals,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Hero'), CombatState::Dead)]),
            self::me(),
        );

        static::assertFalse(VitalsTracking::follow(
            $vitals,
            new PlayerStateUpdatedEvent(new PlayerName('Someone'), CombatState::Idle),
            self::me(),
        ));
        static::assertFalse(VitalsTracking::follow(
            $vitals,
            new PlayerStateUpdatedEvent(new PlayerName('Hero'), CombatState::Dead),
            self::me(),
        ));
        static::assertFalse($vitals->alive());

        VitalsTracking::follow(
            $vitals,
            new PlayerStateUpdatedEvent(new PlayerName('Hero'), CombatState::Idle),
            self::me(),
        );
        static::assertSame([390, 390, 100, 100], self::values($vitals));
    }

    #[Test]
    public function the_real_respawn_takes_the_health_and_mana_it_carries(): void
    {
        $me = Fixture::me();
        foreach ([
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"Hero","o":{"intHPMax":1193,"intMPMax":100,"intHP":990,"intMP":20}}}}',
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"Hero","o":{"intState":0}}}}',
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"Hero","o":{"intState":1,"intHP":1193,"intMP":100,"intSP":100,"strFrame":"Enter","strPad":"Spawn"}}}}',
        ] as $message) {
            foreach (Events::recognize(JsonMessage::from($message)->unwrap()) as $event) {
                $me->hear($event);
            }
        }

        static::assertSame([1193, 1193, 100, 100], self::values($me->hero()->vitals));
    }

    #[Test]
    public function leaving_combat_alive_keeps_the_health_it_has(): void
    {
        $vitals = Fixture::vitals(health: 189);

        static::assertFalse(VitalsTracking::follow(
            $vitals,
            new PlayerStateUpdatedEvent(new PlayerName('Hero'), CombatState::Idle),
            self::me(),
        ));
        static::assertSame([189, 390, 100, 100], self::values($vitals));
    }

    #[Test]
    public function someone_else_falling_leaves_my_health_alone(): void
    {
        static::assertFalse(VitalsTracking::follow(
            Fixture::vitals(health: 189),
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Partner'), CombatState::Dead)]),
            self::me(),
        ));
    }

    #[Test]
    public function a_fight_between_others_leaves_my_health_and_mana_alone(): void
    {
        $vitals = Fixture::vitals(health: 189, mana: 20);

        static::assertFalse(VitalsTracking::follow(
            $vitals,
            new PlayersHealthChangedEvent([new PlayerHealth(new PlayerName('Someone'), new Health(1))]),
            self::me(),
        ));
        static::assertFalse(VitalsTracking::follow(
            $vitals,
            new PlayersManaChangedEvent([new PlayerMana(new PlayerName('Someone'), new Mana(1))]),
            self::me(),
        ));
    }

    private static function me(): Identity
    {
        return new Identity(new PlayerSpawnId(122_628), new PlayerName('Hero'));
    }

    /** @return list<int> */
    private static function values(Vitals $vitals): array
    {
        return [
            $vitals->health->current->value,
            $vitals->health->maximum->value,
            $vitals->mana->current->value,
            $vitals->mana->maximum->value,
        ];
    }
}
