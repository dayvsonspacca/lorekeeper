<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;

final readonly class MemberTracking
{
    public static function follow(Player $player, Event $event, Identity $me): bool
    {
        $member = $player->tags->has(PlayerTag::Member);
        if (!$event instanceof PlayerDataLoadedEvent) {
            return $member;
        }

        return $member || (PlayerDataTracking::mine($event, $me)->member ?? false);
    }
}
