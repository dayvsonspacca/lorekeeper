<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scripts;

use Lorekeeper\Apps\Desktop\Scripts\Flow;
use Lorekeeper\Apps\Desktop\Scripts\FlowFile;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;

final readonly class Flows
{
    /**
     * @param non-empty-list<array<string, mixed>> $nodes
     * @param list<array<string, string>> $wires
     */
    public static function flow(array $nodes, array $wires = [], string $name = 'Test'): Flow
    {
        return FlowFile::fromArray([
            'lorekeeper' => 1,
            'name' => $name,
            'start' => $nodes[0]['id'],
            'nodes' => $nodes,
            'wires' => $wires,
        ]);
    }

    /**
     * @param Script<object> $script
     * @param list<null|Event|TimedOut> $answers
     *
     * @return array{list<Instruction>, mixed}
     */
    public static function play(Script $script, Character $me, array $answers): array
    {
        $running = $script->run($me);
        $instructions = [];
        while ($running->valid()) {
            $instructions[] = $running->current();
            $running->send(array_shift($answers));
        }

        return [$instructions, $running->getReturn()];
    }
}
