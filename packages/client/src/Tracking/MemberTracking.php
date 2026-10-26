<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\Hero\HeroDataLoadedEvent;

final readonly class MemberTracking
{
    public static function follow(Hero $hero, Event $event, Identity $me): bool
    {
        if (
            !$event instanceof HeroDataLoadedEvent
            || $hero->tags->has(PlayerTag::Member)
            || !(HeroDataTracking::mine($event, $me)->member ?? false)
        ) {
            return false;
        }

        $hero->tags->add(PlayerTag::Member);

        return true;
    }
}
