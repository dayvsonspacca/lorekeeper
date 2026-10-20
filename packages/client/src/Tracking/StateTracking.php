<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;

final readonly class StateTracking
{
    public static function follow(CombatState $state, Event $event, Identity $me): CombatState
    {
        return match (true) {
            $event instanceof MovedToMapEvent => self::arrived($state, $event, $me),
            $event instanceof PlayersStateChangedEvent => self::changed($state, $event, $me),
            $event instanceof PlayerStateUpdatedEvent && $me->is($event->name) => $event->state,
            $event instanceof PlayerDiedEvent => CombatState::Dead,
            $event instanceof ResurrectedEvent => CombatState::Idle,
            default => $state,
        };
    }

    private static function changed(CombatState $state, PlayersStateChangedEvent $event, Identity $me): CombatState
    {
        foreach ($event->players as $player) {
            if ($me->is($player->player)) {
                return $player->state;
            }
        }

        return $state;
    }

    private static function arrived(CombatState $state, MovedToMapEvent $event, Identity $me): CombatState
    {
        foreach ($event->players as $seen) {
            if ($me->owns($seen->socketId)) {
                return $seen->player->state;
            }
        }

        return $state;
    }
}
