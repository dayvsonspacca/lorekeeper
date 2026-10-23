<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;

final readonly class PlayerDataTracking
{
    public static function mine(PlayerDataLoadedEvent $event, Identity $me): ?PlayerData
    {
        foreach ($event->players as $player) {
            if ($me->owns($player->spawnId)) {
                return $player;
            }
        }

        return null;
    }
}
