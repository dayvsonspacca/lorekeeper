<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Server\Event\Player\Hero\HeroData;
use Lorekeeper\Server\Event\Player\Hero\HeroDataLoadedEvent;

final readonly class HeroDataTracking
{
    public static function mine(HeroDataLoadedEvent $event, Identity $me): ?HeroData
    {
        return $me->owns($event->data->spawnId) ? $event->data : null;
    }
}
