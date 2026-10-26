<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Game\Attribute\Stat\Stats;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\Hero\HeroStatsUpdatedEvent;

final readonly class StatsTracking
{
    public static function follow(Stats $stats, Event $event): bool
    {
        if (!$event instanceof HeroStatsUpdatedEvent) {
            return false;
        }

        foreach ($event->stats as $stat) {
            $stats->update($stat);
        }

        return true;
    }
}
