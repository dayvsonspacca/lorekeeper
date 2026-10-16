<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

final readonly class Ending
{
    public function __construct(
        public SessionStatus $status,
        public ?string $reason = null,
    ) {}
}
