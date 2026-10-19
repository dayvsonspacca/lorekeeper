<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Client\Tracking\AwayTracking;
use Lorekeeper\Client\Tracking\ClassTracking;
use Lorekeeper\Client\Tracking\DropTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Client\Tracking\LevelTracking;
use Lorekeeper\Client\Tracking\LocationTracking;
use Lorekeeper\Client\Tracking\StateTracking;
use Lorekeeper\Client\Tracking\VitalsTracking;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\SocketId;
use Psl\Observer\SubjectInterface;

final class Character implements SubjectInterface
{
    use Observable;

    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        private readonly SocketId $socketId,
        private Player $player,
        private Location $location,
        private CombatState $state,
        private bool $away,
        private Drops $drops = new Drops(),
    ) {}

    public function socketId(): SocketId
    {
        return $this->socketId;
    }

    public function player(): Player
    {
        return $this->player;
    }

    public function location(): Location
    {
        return $this->location;
    }

    public function state(): CombatState
    {
        return $this->state;
    }

    public function away(): bool
    {
        return $this->away;
    }

    public function drops(): Drops
    {
        return $this->drops;
    }

    public function hear(Event $event): void
    {
        $me = new Identity($this->socketId, $this->player->name);
        $was = $this->player;

        $level = LevelTracking::follow($was->level, $event);
        $vitals = VitalsTracking::follow($was->vitals, $event, $me);
        $class = ClassTracking::follow($was->class, $event, $me);
        $inventory = InventoryTracking::follow($was->inventory, $event, $this->drops);
        $drops = DropTracking::follow($this->drops, $event);
        $location = LocationTracking::follow($this->location, $event, $me);
        $state = StateTracking::follow($this->state, $event, $me);
        $away = AwayTracking::follow($this->away, $event, $me);

        if (
            $level === $was->level
            && $vitals === $was->vitals
            && $class === $was->class
            && $inventory === $was->inventory
            && $location === $this->location
            && $state === $this->state
            && $away === $this->away
            && $drops === $this->drops
        ) {
            return;
        }

        $this->player = new Player($was->name, $level, $vitals, $class, $inventory);
        $this->location = $location;
        $this->state = $state;
        $this->away = $away;
        $this->drops = $drops;
        $this->notify();
    }
}
