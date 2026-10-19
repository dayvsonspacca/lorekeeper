<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Moves;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Map\MoveToCellCommand;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Event;
use Psl\DateTime\Duration;

trait Jumps
{
    abstract protected function me(): Character;

    abstract protected function send(Command $command): Send;

    /** @return Generator<int, Instruction, null|Event|TimedOut, void> */
    protected function jump(MapSpot $spot): Generator
    {
        yield $this->send(new MoveToCellCommand($this->me()->location()->area, $spot));
        yield new Pause(Duration::seconds(1));
    }
}
