<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Client\Location;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;

final readonly class LocationTracking
{
    public static function follow(Location $location, Event $event, Identity $me): Location
    {
        if (!$event instanceof MovedToMapEvent) {
            return $location;
        }

        $spot = $event
            ->map
            ->players
            ->find($me->spawnId)
            ->map(static fn(MapPlayer $seen): MapSpot => $seen->spot)
            ->unwrapOr($location->spot);

        return new Location($event->areaId, $event->map, $spot);
    }
}
