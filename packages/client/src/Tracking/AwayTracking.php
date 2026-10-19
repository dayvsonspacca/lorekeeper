<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Server\Event\AwayFromKeyboardEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerAwayUpdatedEvent;

final readonly class AwayTracking
{
    /** @mago-expect lint:no-boolean-flag-parameter */
    public static function follow(bool $away, Event $event, Identity $me): bool
    {
        if ($event instanceof AwayFromKeyboardEvent) {
            return true;
        }

        if ($event instanceof PlayerAwayUpdatedEvent) {
            return $me->is($event->name) ? $event->away : $away;
        }

        if (!$event instanceof MovedToMapEvent) {
            return $away;
        }

        foreach ($event->players as $seen) {
            if ($me->owns($seen->socketId)) {
                return $seen->player->away;
            }
        }

        return $away;
    }
}
