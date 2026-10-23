<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\Hero\Progression\QuestsProgression;
use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;

final readonly class QuestTracking
{
    public static function follow(QuestsProgression $quests, Event $event, Identity $me): bool
    {
        return match (true) {
            $event instanceof PlayerDataLoadedEvent => self::recounted($quests, PlayerDataTracking::mine($event, $me)),
            $event instanceof QuestMarkedEvent && !$quests->has($event->quest) => self::completed($quests, $event),
            default => false,
        };
    }

    private static function recounted(QuestsProgression $quests, ?PlayerData $data): bool
    {
        if ($data === null) {
            return false;
        }

        foreach (QuestGroup::cases() as $group) {
            $quests->reset($group);
        }

        foreach ($data->quests as $quest) {
            $quests->complete($quest);
        }

        return true;
    }

    private static function completed(QuestsProgression $quests, QuestMarkedEvent $event): bool
    {
        $quests->complete($event->quest);

        return true;
    }
}
