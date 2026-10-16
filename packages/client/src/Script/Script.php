<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

use Generator;
use Lorekeeper\Server\Event\Event;

/** @template TResult */
interface Script
{
    /** @return Generator<int, Instruction, null|Event|TimedOut, TResult> */
    public function run(): Generator;
}
