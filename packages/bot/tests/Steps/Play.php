<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps;

use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
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
            if ($steps->current() instanceof Progress) {
                $steps->send(null);

                continue;
            }

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

    /**
     * @param list<Instruction> $instructions
     *
     * @return list<string>
     */
    public static function sent(array $instructions): array
    {
        return array_values(array_map(
            static fn(Send $send): string => $send->command->toMessage(),
            array_filter($instructions, static fn(Instruction $instruction): bool => $instruction instanceof Send),
        ));
    }
}
