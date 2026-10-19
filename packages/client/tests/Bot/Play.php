<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot;

use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;

final readonly class Play
{
    /**
     * @param Script<mixed> $script
     * @param list<null|Event|TimedOut|non-empty-list<Event>> $answers
     *
     * @return array{list<Instruction>, mixed}
     */
    public static function run(Script $script, Character $me, array $answers): array
    {
        $steps = $script->run($me);
        $instructions = [];

        while ($steps->valid()) {
            $instructions[] = $steps->current();
            $answer = array_shift($answers);
            $heard = is_array($answer) ? $answer : [$answer];
            foreach ($heard as $event) {
                if (!$event instanceof Event) {
                    continue;
                }

                $me->hear($event);
            }

            $steps->send(is_array($answer) ? $answer[0] : $answer);
        }

        return [$instructions, $steps->getReturn()];
    }
}
