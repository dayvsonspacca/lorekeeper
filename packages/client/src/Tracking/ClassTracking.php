<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Reward\RewardedEvent;

final readonly class ClassTracking
{
    public static function follow(PlayerClass $class, Event $event, Identity $me): PlayerClass
    {
        return match (true) {
            $event instanceof ClassChangedEvent => $me->owns($event->player) ? self::changed($event) : $class,
            $event instanceof SkillsLoadedEvent => self::learned($class, $event),
            $event instanceof RewardedEvent => self::earned($class, $event->classPoints),
            default => $class,
        };
    }

    private static function changed(ClassChangedEvent $event): PlayerClass
    {
        return new PlayerClass(
            new CharacterClass($event->className, $event->category, $event->description, [], []),
            $event->points,
            [],
        );
    }

    private static function learned(PlayerClass $class, SkillsLoadedEvent $event): PlayerClass
    {
        return new PlayerClass(
            new CharacterClass(
                $class->class->name,
                $class->class->category,
                $class->class->description,
                array_map(static fn(PlayerSkill $skill): Skill => $skill->skill, $event->skills),
                $event->passives,
            ),
            $class->points,
            $event->skills,
        );
    }

    private static function earned(PlayerClass $class, ClassPoints $points): PlayerClass
    {
        if ($points->value === 0) {
            return $class;
        }

        return new PlayerClass($class->class, new ClassPoints($class->points->value + $points->value), $class->skills);
    }
}
