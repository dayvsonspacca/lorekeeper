<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

final readonly class Outcome
{
    public function __construct(
        public JobStatus $status,
        public string $summary,
    ) {}
}
