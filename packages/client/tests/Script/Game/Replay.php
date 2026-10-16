<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;

final readonly class Replay
{
    /**
     * @param Script<mixed> $script
     *
     * @return array{list<Instruction>, mixed}
     */
    public static function run(Script $script, null|Event|TimedOut ...$answers): array
    {
        $steps = $script->run();
        $instructions = [];

        while ($steps->valid()) {
            $instructions[] = $steps->current();
            $steps->send(array_shift($answers));
        }

        return [$instructions, $steps->getReturn()];
    }
}
