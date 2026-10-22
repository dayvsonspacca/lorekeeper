<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Steps;

use Generator;
use Lorekeeper\Bot\Abilities\Moves\Hunts;
use Lorekeeper\Bot\Abilities\Moves\Joins;
use Lorekeeper\Bot\Abilities\Moves\Rests;
use Lorekeeper\Bot\Abilities\Paces;
use Lorekeeper\Bot\Abilities\Quests\Quests;
use Lorekeeper\Bot\BotScript;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Event;
use RuntimeException;

abstract class StepScript extends BotScript
{
    use Joins, Hunts, Quests, Rests, Paces {
        Joins::join as public;
        Hunts::hunt as public;
        Hunts::kill as public;
        Hunts::attackIn as public;
        Hunts::collect as public;
        Quests::alreadyDone as public;
        Quests::accept as public;
        Quests::turnIn as public;
        Rests::rest as public;
        Paces::paced as public;
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    public function moveTo(MapSpot $spot): Generator
    {
        yield from $this->jump($spot);
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, null|Failed> */
    final public function attempt(Action $action): Generator
    {
        try {
            $playing = $action->play($this);
            yield from $playing;

            return $playing->getReturn();
        } catch (RuntimeException $broke) {
            return new Failed($broke->getMessage());
        }
    }
}
