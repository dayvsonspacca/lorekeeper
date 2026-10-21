<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps;

use Generator;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use RuntimeException;

interface Action
{
    public function describe(): string;

    /**
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, null|Failed>
     */
    public function play(StepScript $bot): Generator;
}
