<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

final readonly class Disconnected
{
    public function __construct(
        public string $reason,
    ) {}
}
