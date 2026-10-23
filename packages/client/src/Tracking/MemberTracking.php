<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;

final readonly class MemberTracking
{
    public static function follow(Hero $hero, Event $event, Identity $me): bool
    {
        if (
            !$event instanceof PlayerDataLoadedEvent
            || $hero->tags->has(PlayerTag::Member)
            || !(PlayerDataTracking::mine($event, $me)->member ?? false)
        ) {
            return false;
        }

        $hero->tags->add(PlayerTag::Member);

        return true;
    }
}
