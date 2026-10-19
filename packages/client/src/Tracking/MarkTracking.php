<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\PlayerQuestMarks;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;

final readonly class MarkTracking
{
    public static function follow(PlayerQuestMarks $marks, Event $event, Identity $me): PlayerQuestMarks
    {
        return match (true) {
            $event instanceof PlayerDataLoadedEvent => PlayerDataTracking::mine($event, $me)->marks ?? $marks,
            $event instanceof QuestMarkedEvent && !$marks->has($event->mark) => new PlayerQuestMarks([
                ...$marks->marks,
                $event->mark,
            ]),
            default => $marks,
        };
    }
}
