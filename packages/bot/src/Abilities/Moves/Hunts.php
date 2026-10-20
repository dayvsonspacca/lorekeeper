<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Moves;

use Generator;
use Lorekeeper\Bot\Abilities\Quests\Loots;
use Lorekeeper\Client\Command\Combat\Attack;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Event\Event;
use Psl\DateTime\Duration;
use RuntimeException;

trait Hunts
{
    use Kills;
    use Loots;

    /**
     * @throws RuntimeException
     *
     * @return Generator<int, Instruction, null|Event|TimedOut, bool>
     */
    protected function hunt(MapSpot $spot, Attack $attack, ItemId $item, Quantity $wanted, Duration $limit): Generator
    {
        $deadline = $this->now()->plus($limit);
        while (!$this->holds($item, $wanted) && !$this->now()->after($deadline)) {
            yield from $this->kill(
                $spot,
                $attack,
                fn(): bool => (
                    $this->holds($item, $wanted)
                    || $this->me()->drops()->find($item) !== null
                    || $this->now()->after($deadline)
                ),
            );
            if ($this->me()->drops()->find($item) === null) {
                continue;
            }

            $pickingUp = $this->pickUp($item);
            yield from $pickingUp;
            if (!$pickingUp->getReturn()) {
                break;
            }
        }

        return $this->holds($item, $wanted);
    }

    private function holds(ItemId $item, Quantity $wanted): bool
    {
        return $this
            ->me()
            ->player()
            ->inventory->quantity($item) >= $wanted->value;
    }
}
