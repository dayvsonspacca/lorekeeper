<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;

final readonly class AwayTracking
{
    /** @mago-expect lint:no-boolean-flag-parameter */
    public static function follow(bool $away, Event $event, Identity $me): bool
    {
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
