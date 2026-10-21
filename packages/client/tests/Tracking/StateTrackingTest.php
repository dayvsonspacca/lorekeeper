<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\StateTracking;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StateTrackingTest extends TestCase
{
    #[Test]
    public function it_goes_into_and_out_of_combat_when_a_round_says_so_about_it(): void
    {
        $fighting = new PlayersStateChangedEvent([
            new PlayerState(new PlayerName('Someone'), CombatState::Dead),
            new PlayerState(new PlayerName('LoreKeeper03'), CombatState::Fighting),
        ]);
        $calm = new PlayersStateChangedEvent([new PlayerState(new PlayerName('lorekeeper03'), CombatState::Idle)]);
        $someoneElse = new PlayersStateChangedEvent([new PlayerState(new PlayerName('Someone'), CombatState::Dead)]);

        static::assertSame(CombatState::Fighting, StateTracking::follow(CombatState::Idle, $fighting, self::me()));
        static::assertSame(CombatState::Idle, StateTracking::follow(CombatState::Fighting, $calm, self::me()));
        static::assertSame(CombatState::Fighting, StateTracking::follow(
            CombatState::Fighting,
            $someoneElse,
            self::me(),
        ));
    }

    #[Test]
    public function it_leaves_combat_when_the_server_says_it_is_idle_again(): void
    {
        $calm = new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Idle);
        $someoneElse = new PlayerStateUpdatedEvent(new PlayerName('Someone'), CombatState::Idle);

        static::assertSame(CombatState::Idle, StateTracking::follow(CombatState::Fighting, $calm, self::me()));
        static::assertSame(CombatState::Fighting, StateTracking::follow(
            CombatState::Fighting,
            $someoneElse,
            self::me(),
        ));
    }

    #[Test]
    public function arriving_on_a_map_without_me_in_it_keeps_the_state_it_had(): void
    {
        static::assertSame(CombatState::Fighting, StateTracking::follow(
            CombatState::Fighting,
            Fixture::arrival('battleon', 1),
            self::me(),
        ));
    }

    private static function me(): Identity
    {
        return new Identity(new SocketId(122_628), new PlayerName('lorekeeper03'));
    }
}
