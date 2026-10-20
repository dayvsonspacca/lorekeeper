<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Client\Tracking\AwayTracking;
use Lorekeeper\Client\Tracking\ClassTracking;
use Lorekeeper\Client\Tracking\DropTracking;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Client\Tracking\LocationTracking;
use Lorekeeper\Client\Tracking\MarkTracking;
use Lorekeeper\Client\Tracking\MemberTracking;
use Lorekeeper\Client\Tracking\ProgressTracking;
use Lorekeeper\Client\Tracking\StateTracking;
use Lorekeeper\Client\Tracking\StatsTracking;
use Lorekeeper\Client\Tracking\VitalsTracking;
use Lorekeeper\Client\Tracking\WalletTracking;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerTag;
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

    /** @mago-expect lint:halstead */
    public function hear(Event $event): void
    {
        $me = new Identity($this->socketId, $this->player->name);
        $was = $this->player;

        $progress = ProgressTracking::follow($was->progress, $event, $me);
        $vitals = VitalsTracking::follow($was->vitals, $event, $me);
        $class = ClassTracking::follow($was->class, $event, $me);
        $inventory = InventoryTracking::follow($was->inventory, $event, $me, $this->drops);
        $wallet = WalletTracking::follow($was->wallet, $event, $me);
        $marks = MarkTracking::follow($was->marks, $event, $me);
        $stats = StatsTracking::follow($was->stats, $event);
        $drops = DropTracking::follow($this->drops, $event);
        $location = LocationTracking::follow($this->location, $event, $me);
        $state = StateTracking::follow($this->state, $event, $me);
        $away = AwayTracking::follow($this->away, $event, $me);
        $member = MemberTracking::follow($was, $event, $me);

        if (
            $progress === $was->progress
            && $vitals === $was->vitals
            && $class === $was->class
            && $inventory === $was->inventory
            && $wallet === $was->wallet
            && $marks === $was->marks
            && $stats === $was->stats
            && $member === $was->tags->has(PlayerTag::Member)
            && $drops === $this->drops
            && $location === $this->location
            && $state === $this->state
            && $away === $this->away
        ) {
            return;
        }

        $this->player = self::tagged(
            new Player($was->name, $progress, $vitals, $class, $inventory, $wallet, $marks, $stats),
            $was,
            $member,
        );

        $this->location = $location;
        $this->state = $state;
        $this->away = $away;
        $this->drops = $drops;
        $this->notify();
    }

    /** @mago-expect lint:no-boolean-flag-parameter */
    private static function tagged(Player $player, Player $was, bool $member): Player
    {
        foreach (PlayerTag::cases() as $tag) {
            if (!$was->tags->has($tag)) {
                continue;
            }

            $player->tags->add($tag);
        }

        if ($member) {
            $player->tags->add(PlayerTag::Member);
        }

        return $player;
    }
}
