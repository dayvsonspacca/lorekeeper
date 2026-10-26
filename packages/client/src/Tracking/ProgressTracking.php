<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\Hero\HeroData;
use Lorekeeper\Server\Event\Player\Hero\HeroDataLoadedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\RewardedEvent;

final readonly class ProgressTracking
{
    public static function follow(Hero $hero, Event $event, Identity $me): bool
    {
        return match (true) {
            $event instanceof HeroDataLoadedEvent => self::recounted($hero, HeroDataTracking::mine($event, $me)),
            $event instanceof LeveledUpEvent => self::raised($hero, $event->level, $event->toNextLevel),
            $event instanceof RewardedEvent && $event->experience->value > 0 => self::earned($hero, $event->experience),
            default => false,
        };
    }

    private static function recounted(Hero $hero, ?HeroData $data): bool
    {
        if ($data === null) {
            return false;
        }

        self::raised($hero, $data->level, $data->experience->maximum);
        $hero->progression->level->recount($data->experience);

        return true;
    }

    private static function raised(Hero $hero, PlayerLevel $level, Experience $toNext): bool
    {
        if ($hero->level->value >= $level->value) {
            return false;
        }

        while ($hero->level->value < $level->value) {
            $hero->up($toNext);
        }

        return true;
    }

    private static function earned(Hero $hero, Experience $experience): bool
    {
        $hero->progression->level->earn($experience);

        return true;
    }
}
