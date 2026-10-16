<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Exception;

use Lorekeeper\Server\Event\Event;
use RuntimeException;
use Throwable;

final class MalformedEventException extends RuntimeException
{
    /** @param class-string<Event> $event */
    public function __construct(
        public readonly string $event,
        string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct("Malformed {$event}: {$reason}", previous: $previous);
    }
}
