<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\PlayerLevelProgress;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\RewardedEvent;

final readonly class ProgressTracking
{
    public static function follow(PlayerLevelProgress $progress, Event $event, Identity $me): PlayerLevelProgress
    {
        return match (true) {
            $event instanceof PlayerDataLoadedEvent => PlayerDataTracking::mine($event, $me)->progress ?? $progress,
            $event instanceof LeveledUpEvent && $event->level->value !== $progress->level->value
                => new PlayerLevelProgress($event->level, new Gauge(new Experience(0), $event->toNextLevel)),
            $event instanceof RewardedEvent && $event->experience->value > 0 => self::earned(
                $progress,
                $event->experience,
            ),
            default => $progress,
        };
    }

    private static function earned(PlayerLevelProgress $progress, Experience $earned): PlayerLevelProgress
    {
        $maximum = $progress->experience->maximum;

        return new PlayerLevelProgress(
            $progress->level,
            new Gauge(
                new Experience(min($maximum->value, $progress->experience->current->value + $earned->value)),
                $maximum,
            ),
        );
    }
}
