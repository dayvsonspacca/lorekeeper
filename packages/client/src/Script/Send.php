<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

use Lorekeeper\Client\Command\Command;

final readonly class Send implements Instruction
{
    public function __construct(
        public Command $command,
        public ?Await $await = null,
    ) {}
}
