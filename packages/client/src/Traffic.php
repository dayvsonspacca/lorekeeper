<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

final readonly class Traffic
{
    public function __construct(
        public TrafficDirection $direction,
        public string $message,
    ) {}
}
