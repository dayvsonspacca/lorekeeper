<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

use Lorekeeper\Server\Event\Event;
use Psl\DateTime\Duration;

final readonly class Await implements Instruction
{
    /** @param non-empty-list<class-string<Event>> $events */
    public function __construct(
        public array $events,
        public Duration $timeout,
    ) {}
}
