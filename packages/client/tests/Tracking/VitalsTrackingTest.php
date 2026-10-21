<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\VitalsTracking;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
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
use Lorekeeper\Server\SocketId;
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
        $mine = VitalsTracking::follow(
            $vitals,
            new PlayerVitalsUpdatedEvent(new PlayerName('LoreKeeper03'), Fixture::vitals(2585, 2585, 90, 100)),
            self::me(),
        );

        static::assertSame($vitals, $theirs);
        static::assertSame([2585, 2585, 90, 100], self::values($mine));
    }

    #[Test]
    public function it_keeps_the_same_vitals_when_nothing_changes(): void
    {
        $vitals = Fixture::vitals();

        static::assertSame($vitals, VitalsTracking::follow(
            $vitals,
            new PlayerVitalsUpdatedEvent(new PlayerName('lorekeeper03'), Fixture::vitals()),
            self::me(),
        ));
    }

    #[Test]
    public function a_fight_changes_health_and_mana_but_not_how_high_they_go(): void
    {
        $hit = VitalsTracking::follow(
            Fixture::vitals(),
            new PlayersHealthChangedEvent([
                new PlayerHealth(new PlayerName('Someone'), new Health(1)),
                new PlayerHealth(new PlayerName('lorekeeper03'), new Health(189)),
            ]),
            self::me(),
        );
        $spent = VitalsTracking::follow(
            $hit,
            new PlayersManaChangedEvent([new PlayerMana(new PlayerName('lorekeeper03'), new Mana(87))]),
            self::me(),
        );

        static::assertSame([189, 390, 87, 100], self::values($spent));
    }

    #[Test]
    public function more_than_the_maximum_it_knew_becomes_the_new_maximum(): void
    {
        $healed = VitalsTracking::follow(
            Fixture::vitals(),
            new PlayersHealthChangedEvent([
                new PlayerHealth(new PlayerName('lorekeeper03'), new Health(450)),
            ]),
            self::me(),
        );

        static::assertSame([450, 450, 100, 100], self::values($healed));
    }

    #[Test]
    public function dying_leaves_no_health_and_coming_back_fills_health_and_mana(): void
    {
        $dead = VitalsTracking::follow(
            Fixture::vitals(health: 189, mana: 20),
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('lorekeeper03'), CombatState::Dead)]),
            self::me(),
        );
        $back = VitalsTracking::follow($dead, new ResurrectedEvent(), self::me());

        static::assertFalse($dead->alive());
        static::assertSame([0, 390, 20, 100], self::values($dead));
        static::assertSame([390, 390, 100, 100], self::values($back));
    }

    #[Test]
    public function standing_again_without_a_resurrection_fills_health_and_mana(): void
    {
        $dead = VitalsTracking::follow(
            Fixture::vitals(health: 189, mana: 20),
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('lorekeeper03'), CombatState::Dead)]),
            self::me(),
        );

        $someoneElse = VitalsTracking::follow(
            $dead,
            new PlayerStateUpdatedEvent(new PlayerName('Someone'), CombatState::Idle),
            self::me(),
        );
        $stillDead = VitalsTracking::follow(
            $dead,
            new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Dead),
            self::me(),
        );
        $back = VitalsTracking::follow(
            $dead,
            new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Idle),
            self::me(),
        );

        static::assertFalse($someoneElse->alive());
        static::assertFalse($stillDead->alive());
        static::assertSame([390, 390, 100, 100], self::values($back));
    }

    #[Test]
    public function the_real_respawn_takes_the_health_and_mana_it_carries(): void
    {
        $me = Fixture::me();
        foreach ([
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"lorekeeper03","o":{"intHPMax":1193,"intMPMax":100,"intHP":990,"intMP":20}}}}',
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"lorekeeper03","o":{"intState":0}}}}',
            '{"t":"xt","b":{"r":-1,"o":{"cmd":"uotls","unm":"lorekeeper03","o":{"intState":1,"intHP":1193,"intMP":100,"intSP":100,"strFrame":"Enter","strPad":"Spawn"}}}}',
        ] as $message) {
            foreach (Events::recognize(JsonMessage::from($message)->unwrap()) as $event) {
                $me->hear($event);
            }
        }

        static::assertSame([1193, 1193, 100, 100], self::values($me->player()->vitals));
    }

    #[Test]
    public function leaving_combat_alive_keeps_the_health_it_has(): void
    {
        $hurt = Fixture::vitals(health: 189);

        static::assertSame($hurt, VitalsTracking::follow(
            $hurt,
            new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Idle),
            self::me(),
        ));
    }

    #[Test]
    public function someone_else_falling_leaves_my_health_alone(): void
    {
        $vitals = Fixture::vitals(health: 189);

        static::assertSame($vitals, VitalsTracking::follow(
            $vitals,
            new PlayersStateChangedEvent([new PlayerState(new PlayerName('Partner'), CombatState::Dead)]),
            self::me(),
        ));
    }

    private static function me(): Identity
    {
        return new Identity(new SocketId(122_628), new PlayerName('lorekeeper03'));
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
