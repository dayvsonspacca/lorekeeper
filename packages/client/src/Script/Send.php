<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\EventBundle;
use Psl\DateTime\Duration;

final readonly class Send implements Instruction
{
    public function __construct(
        public Command $command,
        public ?Await $await = null,
    ) {}

    /**
     * @param class-string<Event>|class-string<EventBundle> $event
     * @param class-string<Event>|class-string<EventBundle> ...$more
     */
    public function awaits(Duration $within, string $event, string ...$more): self
    {
        $events = self::concrete($event);
        foreach ($more as $awaited) {
            $events = [...$events, ...self::concrete($awaited)];
        }

        return new self($this->command, new Await(array_values(array_unique($events)), $within));
    }

    /**
     * @param class-string<Event>|class-string<EventBundle> $awaited
     *
     * @return non-empty-list<class-string<Event>>
     */
    private static function concrete(string $awaited): array
    {
        return is_subclass_of($awaited, EventBundle::class) ? $awaited::events() : [$awaited];
    }
}
