<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

use Psl\DateTime\Duration;

final readonly class Pause implements Instruction
{
    public function __construct(
        public Duration $duration,
    ) {}
}
