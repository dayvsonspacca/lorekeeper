<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;

final readonly class LevelTracking
{
    public static function follow(PlayerLevel $level, Event $event): PlayerLevel
    {
        if (!$event instanceof LeveledUpEvent || $event->level->value === $level->value) {
            return $level;
        }

        return $event->level;
    }
}
