<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Quests;

use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Inventory\PickUpDropCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Psl\DateTime\Duration;

trait Loots
{
    abstract protected function me(): Character;

    abstract protected function send(Command $command): Send;

    /** @return Generator<int, Instruction, null|Event|TimedOut, bool> */
    protected function collect(ItemId $reward, int $heldBefore): Generator
    {
        if ($this->me()->hero()->inventory->quantity($reward) > $heldBefore) {
            return true;
        }

        $pickingUp = $this->pickUp($reward);
        yield from $pickingUp;

        return $pickingUp->getReturn();
    }

    /** @return Generator<int, Instruction, null|Event|TimedOut, bool> */
    protected function pickUp(ItemId $item): Generator
    {
        if ($this->me()->drops()->find($item) === null) {
            yield new Await([ItemDroppedEvent::class], Duration::seconds(10));
        }

        if ($this->me()->drops()->find($item) === null) {
            return false;
        }

        /** @var Event|TimedOut $answer */
        $answer = yield $this->send(new PickUpDropCommand($this->me()->hero()->spawnId, $item))->awaits(
            Duration::seconds(10),
            DropPickedUpEvent::class,
        );

        return $answer instanceof DropPickedUpEvent && $answer->item->value === $item->value;
    }
}
