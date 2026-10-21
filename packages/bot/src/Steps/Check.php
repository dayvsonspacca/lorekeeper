<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps;

use Generator;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;

interface Check
{
    public function describe(): string;

    /** @return Generator<int, Instruction, null|Event|TimedOut, bool> */
    public function holds(StepScript $bot): Generator;
}
