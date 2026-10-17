<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Server\Event\Event;

/** @template TResult */
interface Script
{
    public function title(): string;

    /** @return Generator<int, Instruction, null|Event|TimedOut, TResult> */
    public function run(Character $me): Generator;
}
