<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Moves;

use Generator;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Psl\DateTime\Duration;

trait Rests
{
    use Jumps;

    private const int SECONDS_TO_STOP_FIGHTING = 15;

    private const int SECONDS_TO_COOL_DOWN = 5;

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    protected function rest(): Generator
    {
        $fought = $this->me()->state() === CombatState::Fighting;
        if ($this->me()->location()->spot->cell->value !== 'Enter') {
            yield from $this->jump(new MapSpot(new MapCell('Enter'), MapPad::Spawn));
        }

        for ($second = 0; $second < self::SECONDS_TO_STOP_FIGHTING; $second++) {
            if ($this->me()->state() !== CombatState::Fighting) {
                break;
            }

            $fought = true;
            yield new Await([PlayerStateUpdatedEvent::class, PlayersStateChangedEvent::class], Duration::seconds(1));
        }

        if ($fought) {
            yield new Pause(Duration::seconds(self::SECONDS_TO_COOL_DOWN));
        }
    }
}
