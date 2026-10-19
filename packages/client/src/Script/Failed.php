<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

final readonly class Failed
{
    public function __construct(
        public string $reason,
    ) {}
}
