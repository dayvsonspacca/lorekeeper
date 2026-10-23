<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Client\Tracking\AwayTracking;
use Lorekeeper\Client\Tracking\ClassTracking;
use Lorekeeper\Client\Tracking\DropTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Client\Tracking\LocationTracking;
use Lorekeeper\Client\Tracking\MemberTracking;
use Lorekeeper\Client\Tracking\ProgressTracking;
use Lorekeeper\Client\Tracking\QuestTracking;
use Lorekeeper\Client\Tracking\StateTracking;
use Lorekeeper\Client\Tracking\StatsTracking;
use Lorekeeper\Client\Tracking\VitalsTracking;
use Lorekeeper\Client\Tracking\WalletTracking;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Server\Event\Event;
use Psl\Observer\SubjectInterface;

final class Character implements SubjectInterface
{
    use Observable;

    public function __construct(
        private readonly Hero $hero,
        private Location $location,
        private CombatState $state,
        private bool $away,
        private Drops $drops = new Drops(),
    ) {}

    public function hero(): Hero
    {
        return $this->hero;
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

    /** @mago-expect lint:halstead */
    public function hear(Event $event): void
    {
        $hero = $this->hero;
        $me = new Identity($hero->spawnId, $hero->name);

        $changed = [
            ProgressTracking::follow($hero, $event, $me),
            VitalsTracking::follow($hero->vitals, $event, $me),
            ClassTracking::follow($hero->class, $event, $me),
            InventoryTracking::follow($hero->inventory, $event, $me, $this->drops),
            WalletTracking::follow($hero->inventory->wallet, $event, $me),
            QuestTracking::follow($hero->progression->quests, $event, $me),
            StatsTracking::follow($hero->attributes->stats, $event),
            MemberTracking::follow($hero, $event, $me),
        ];

        $drops = DropTracking::follow($this->drops, $event);
        $location = LocationTracking::follow($this->location, $event, $me);
        $state = StateTracking::follow($this->state, $event, $me);
        $away = AwayTracking::follow($this->away, $event, $me);

        if (
            !in_array(true, $changed, strict: true)
            && $drops === $this->drops
            && $location === $this->location
            && $state === $this->state
            && $away === $this->away
        ) {
            return;
        }

        $this->drops = $drops;
        $this->location = $location;
        $this->state = $state;
        $this->away = $away;
        $this->notify();
    }
}
