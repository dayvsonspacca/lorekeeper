<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

final readonly class Outcome
{
    public function __construct(
        public OutcomeStatus $status,
        public string $summary,
    ) {}
}
