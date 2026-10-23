<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Item\Equipment\CharacterClass\CharacterClass;
use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Reward\RewardedEvent;

final readonly class ClassTracking
{
    public static function follow(HeroClass $class, Event $event, Identity $me): bool
    {
        return match (true) {
            $event instanceof ClassChangedEvent && $me->owns($event->player) => self::changed($class, $event),
            $event instanceof SkillsLoadedEvent => self::learned($class, $event),
            $event instanceof RewardedEvent && $event->classPoints->value > 0 => self::earned($class, $event),
            default => false,
        };
    }

    private static function changed(HeroClass $class, ClassChangedEvent $event): bool
    {
        $class->change(
            new CharacterClass($event->className, $event->category, $event->description, [], []),
            $event->points,
        );

        return true;
    }

    private static function learned(HeroClass $class, SkillsLoadedEvent $event): bool
    {
        $class->learn($event->skills, $event->passives);

        return true;
    }

    private static function earned(HeroClass $class, RewardedEvent $event): bool
    {
        $class->earn($event->classPoints);

        return true;
    }
}
