<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;

final readonly class VitalsTracking
{
    public static function follow(Vitals $vitals, Event $event, Identity $me): Vitals
    {
        $next = match (true) {
            $event instanceof PlayerVitalsUpdatedEvent => $me->is($event->name) ? $event->vitals : $vitals,
            $event instanceof PlayersHealthChangedEvent => self::hit($vitals, $event, $me),
            $event instanceof PlayersManaChangedEvent => self::spent($vitals, $event, $me),
            $event instanceof PlayerDiedEvent => self::health($vitals, new Health(0)),
            $event instanceof ResurrectedEvent => self::health($vitals, $vitals->health->maximum),
            default => $vitals,
        };

        return self::same($vitals, $next) ? $vitals : $next;
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
