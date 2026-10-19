<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

use Lorekeeper\Server\Event\Event;

final readonly class Outcome
{
    public function __construct(
        public OutcomeStatus $status,
        public string $summary,
    ) {}

    public static function of(mixed $result): self
    {
        return match (true) {
            $result instanceof Done => new self(OutcomeStatus::Done, $result->summary),
            $result instanceof Disconnected => new self(OutcomeStatus::Disconnected, $result->reason),
            $result instanceof Stopped => new self(OutcomeStatus::Cancelled, 'cancelled'),
            default => new self(OutcomeStatus::Failed, self::reason($result)),
        };
    }

    private static function reason(mixed $result): string
    {
        return match (true) {
            is_string($result) => $result,
            $result instanceof TimedOut => 'no answer while waiting for '
                . implode(', ', array_map(static fn(string $event): string => substr(
                    $event,
                    (int) strrpos($event, needle: '\\') + 1,
                ), $result->await->events)),
            $result instanceof Event => $result->summary(),
            default => 'the script ended',
        };
    }
}
