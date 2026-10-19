<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Client\Location;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;

final readonly class LocationTracking
{
    public static function follow(Location $location, Event $event, Identity $me): Location
    {
        if (!$event instanceof MovedToMapEvent) {
            return $location;
        }

        $spot = $location->spot;
        foreach ($event->players as $seen) {
            if (!$me->owns($seen->socketId)) {
                continue;
            }

            $spot = $seen->player->spot;
        }

        return new Location($event->areaId, $event->map, $spot);
    }
}
