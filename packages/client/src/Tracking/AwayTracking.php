<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerAwayUpdatedEvent;

final readonly class AwayTracking
{
    /** @mago-expect lint:no-boolean-flag-parameter */
    public static function follow(bool $away, Event $event, Identity $me): bool
    {
        if ($event instanceof PlayerAwayUpdatedEvent && $me->is($event->name)) {
            return $event->away;
        }

        return $away;
    }
}
