<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final readonly class VitalsTracking
{
    public static function follow(Vitals $vitals, Event $event, Identity $me): bool
    {
        $before = self::reading($vitals);

        match (true) {
            $event instanceof PlayerVitalsUpdatedEvent && $me->is($event->name) => $vitals->recount(
                $event->vitals->health,
                $event->vitals->mana,
            ),
            $event instanceof PlayersHealthChangedEvent => self::hit($vitals, $event, $me),
            $event instanceof PlayersManaChangedEvent => self::spent($vitals, $event, $me),
            $event instanceof PlayersStateChangedEvent => self::changed($vitals, $event, $me),
            $event instanceof PlayerStateUpdatedEvent && $me->is($event->name) => self::updated($vitals, $event),
            $event instanceof ResurrectedEvent => $vitals->revive(),
            default => null,
        };

        return self::reading($vitals) !== $before;
    }

    private static function hit(Vitals $vitals, PlayersHealthChangedEvent $event, Identity $me): void
    {
        foreach ($event->players as $player) {
            if (!$me->is($player->player)) {
                continue;
            }

            self::health($vitals, $player->health);
        }
    }

    private static function spent(Vitals $vitals, PlayersManaChangedEvent $event, Identity $me): void
    {
        foreach ($event->players as $player) {
            if (!$me->is($player->player)) {
                continue;
            }

            self::mana($vitals, $player->mana);
        }
    }

    private static function changed(Vitals $vitals, PlayersStateChangedEvent $event, Identity $me): void
    {
        foreach ($event->players as $player) {
            if (!$me->is($player->player)) {
                continue;
            }

            self::stood($vitals, $player->state);
        }
    }

    private static function updated(Vitals $vitals, PlayerStateUpdatedEvent $event): void
    {
        self::stood($vitals, $event->state);
        if ($event->health !== null) {
            self::health($vitals, $event->health);
        }

        if ($event->mana !== null) {
            self::mana($vitals, $event->mana);
        }
    }

    private static function stood(Vitals $vitals, CombatState $state): void
    {
        if ($state === CombatState::Dead) {
            $vitals->die();

            return;
        }

        $vitals->revive();
    }

    private static function health(Vitals $vitals, Health $now): void
    {
        if ($now->value > $vitals->health->maximum->value) {
            $vitals->recount(new Gauge($now, $now), $vitals->mana);

            return;
        }

        $change = $now->value - $vitals->health->current->value;
        match ($change <=> 0) {
            -1 => $vitals->hurt(new Health(abs($change))),
            1 => $vitals->heal(new Health(abs($change))),
            default => null,
        };
    }

    private static function mana(Vitals $vitals, Mana $now): void
    {
        if ($now->value > $vitals->mana->maximum->value) {
            $vitals->recount($vitals->health, new Gauge($now, $now));

            return;
        }

        $change = $now->value - $vitals->mana->current->value;
        match ($change <=> 0) {
            -1 => $vitals->spend(new Mana(abs($change))),
            1 => $vitals->regain(new Mana(abs($change))),
            default => null,
        };
    }

    /** @return list<int> */
    private static function reading(Vitals $vitals): array
    {
        return [
            $vitals->health->current->value,
            $vitals->health->maximum->value,
            $vitals->mana->current->value,
            $vitals->mana->maximum->value,
        ];
    }
}
