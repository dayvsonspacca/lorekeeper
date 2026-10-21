<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;

/** @mago-expect lint:cyclomatic-complexity */
final readonly class VitalsTracking
{
    public static function follow(Vitals $vitals, Event $event, Identity $me): Vitals
    {
        $next = match (true) {
            $event instanceof PlayerVitalsUpdatedEvent => $me->is($event->name) ? $event->vitals : $vitals,
            $event instanceof PlayersHealthChangedEvent => self::hit($vitals, $event, $me),
            $event instanceof PlayersManaChangedEvent => self::spent($vitals, $event, $me),
            $event instanceof PlayersStateChangedEvent => self::changed($vitals, $event, $me),
            $event instanceof PlayerStateUpdatedEvent => self::updated($vitals, $event, $me),
            $event instanceof ResurrectedEvent => self::full($vitals),
            default => $vitals,
        };

        return self::same($vitals, $next) ? $vitals : $next;
    }

    private static function changed(Vitals $vitals, PlayersStateChangedEvent $event, Identity $me): Vitals
    {
        foreach ($event->players as $player) {
            if ($me->is($player->player)) {
                return self::stood($vitals, $player->state);
            }
        }

        return $vitals;
    }

    private static function updated(Vitals $vitals, PlayerStateUpdatedEvent $event, Identity $me): Vitals
    {
        if (!$me->is($event->name)) {
            return $vitals;
        }

        $stood = self::stood($vitals, $event->state);
        $healed = $event->health === null ? $stood : self::health($stood, $event->health);

        return $event->mana === null ? $healed : self::mana($healed, $event->mana);
    }

    private static function stood(Vitals $vitals, CombatState $state): Vitals
    {
        return match (true) {
            $state === CombatState::Dead => self::health($vitals, new Health(0)),
            $vitals->alive() => $vitals,
            default => self::full($vitals),
        };
    }

    private static function full(Vitals $vitals): Vitals
    {
        return new Vitals(
            new Gauge($vitals->health->maximum, $vitals->health->maximum),
            new Gauge($vitals->mana->maximum, $vitals->mana->maximum),
        );
    }

    private static function hit(Vitals $vitals, PlayersHealthChangedEvent $event, Identity $me): Vitals
    {
        foreach ($event->players as $player) {
            if ($me->is($player->player)) {
                return self::health($vitals, $player->health);
            }
        }

        return $vitals;
    }

    private static function spent(Vitals $vitals, PlayersManaChangedEvent $event, Identity $me): Vitals
    {
        foreach ($event->players as $player) {
            if ($me->is($player->player)) {
                return self::mana($vitals, $player->mana);
            }
        }

        return $vitals;
    }

    private static function health(Vitals $vitals, Health $health): Vitals
    {
        $maximum = $vitals->health->maximum;

        return new Vitals(new Gauge($health, $health->value > $maximum->value ? $health : $maximum), $vitals->mana);
    }

    private static function mana(Vitals $vitals, Mana $mana): Vitals
    {
        $maximum = $vitals->mana->maximum;

        return new Vitals($vitals->health, new Gauge($mana, $mana->value > $maximum->value ? $mana : $maximum));
    }

    private static function same(Vitals $one, Vitals $other): bool
    {
        return (
            $one->health->current->value === $other->health->current->value
            && $one->health->maximum->value === $other->health->maximum->value
            && $one->mana->current->value === $other->mana->current->value
            && $one->mana->maximum->value === $other->mana->maximum->value
        );
    }
}
