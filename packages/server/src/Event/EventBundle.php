<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

abstract readonly class EventBundle
{
    /** @return non-empty-list<class-string<Event>> */
    abstract public static function events(): array;

    final public static function contains(mixed $answer): bool
    {
        foreach (static::events() as $event) {
            if ($answer instanceof $event) {
                return true;
            }
        }

        return false;
    }
}
