<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

final readonly class TimedOut
{
    public function __construct(
        public Await $await,
    ) {}
}
