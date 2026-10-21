<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

use LogicException;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Location;
use Lorekeeper\Client\Tracking\StatsTracking;
use Lorekeeper\Core\Attribute\Stat\Stats;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Event\Player\StatsUpdatedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use Lorekeeper\Server\SocketId;

/** @mago-expect lint:cyclomatic-complexity */
final class Entrance
{
    private ?SocketId $socketId = null;

    private ?MovedToMapEvent $arrival = null;

    private ?PlayerItems $inventory = null;

    private ?PlayerData $data = null;

    private ?ClassChangedEvent $class = null;

    private ?SkillsLoadedEvent $skills = null;

    private ?Vitals $vitals = null;

    private Stats $stats;

    public function __construct(
        private readonly PlayerName $name,
    ) {
        $this->stats = new Stats();
    }

    /** @mago-expect lint:halstead */
    public function hear(Event $event): void
    {
        match (true) {
            $event instanceof LoginSucceededEvent => $this->socketId = $event->socketId,
            $event instanceof MovedToMapEvent => $this->arrival = $event,
            $event instanceof InventoryLoadedEvent => $this->inventory = $event->items,
            $event instanceof PlayerDataLoadedEvent => $this->data = $this->mine($event) ?? $this->data,
            $event instanceof ClassChangedEvent => $this->isMine($event->player) ? ($this->class = $event) : null,
            $event instanceof SkillsLoadedEvent => $this->skills = $event,
            $event instanceof StatsUpdatedEvent => $this->stats = StatsTracking::follow($this->stats, $event),
            $event instanceof PlayerVitalsUpdatedEvent => $this->isMe($event->name)
                ? ($this->vitals = $event->vitals)
                : null,
            default => null,
        };
    }

    /** @return list<class-string<Event>> */
    public function missing(): array
    {
        return array_keys(array_filter([
            LoginSucceededEvent::class => $this->socketId === null,
            MovedToMapEvent::class => $this->me() === null,
            InventoryLoadedEvent::class => $this->inventory === null,
            PlayerDataLoadedEvent::class => $this->data === null,
            ClassChangedEvent::class => $this->class === null,
            SkillsLoadedEvent::class => $this->skills === null,
            PlayerVitalsUpdatedEvent::class => $this->vitals === null,
        ]));
    }

    /** @throws LogicException */
    public function character(): Character
    {
        $socketId = $this->socketId;
        $arrival = $this->arrival;
        $me = $this->me();
        $inventory = $this->inventory;
        $class = $this->class;
        $skills = $this->skills;
        $vitals = $this->vitals;
        $data = $this->data;

        if (
            $socketId === null
            || $arrival === null
            || $me === null
            || $inventory === null
            || $class === null
            || $skills === null
            || $vitals === null
            || $data === null
        ) {
            throw new LogicException('Still waiting for ' . implode(', ', $this->missing()) . '.');
        }

        $player = new Player(
            $this->name,
            $data->progress,
            $vitals,
            new PlayerClass(
                new CharacterClass(
                    $class->className,
                    $class->category,
                    $class->description,
                    array_map(static fn(PlayerSkill $skill): Skill => $skill->skill, $skills->skills),
                    $skills->passives,
                ),
                $class->points,
                $skills->skills,
            ),
            new PlayerInventory($inventory, $data->bagSlots),
            $data->wallet,
            $data->marks,
            $this->stats,
        );
        if ($data->member) {
            $player->tags->add(PlayerTag::Member);
        }

        return new Character(
            $socketId,
            $player,
            new Location($arrival->areaId, $arrival->roomId, $arrival->map, $me->spot),
            $me->state,
            $me->away,
        );
    }

    private function me(): ?MapPlayer
    {
        foreach ($this->arrival->players ?? [] as $seen) {
            if ($this->isMine($seen->socketId)) {
                return $seen->player;
            }
        }

        return null;
    }

    private function mine(PlayerDataLoadedEvent $event): ?PlayerData
    {
        foreach ($event->players as $player) {
            if ($this->isMine($player->socketId)) {
                return $player;
            }
        }

        return null;
    }

    private function isMine(SocketId $socketId): bool
    {
        return $this->socketId?->value === $socketId->value;
    }

    private function isMe(PlayerName $name): bool
    {
        return strcasecmp($name->value, $this->name->value) === 0;
    }
}
