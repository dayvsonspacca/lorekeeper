<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

final readonly class Ending
{
    public function __construct(
        public EndingStatus $status,
        public ?string $reason = null,
    ) {}
}
