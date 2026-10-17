<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use LogicException;
use Lorekeeper\Client\Bot\MapLayout;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MonstersPlacedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Map\PlayersPresentEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\SocketId;
use Psl\Observer\SubjectInterface;

/** @mago-expect lint:too-many-methods */
final class Character implements SubjectInterface
{
    use Observable;

    private ?SocketId $socket = null;

    private ?Position $position = null;

    private ?PlayerLevel $level = null;

    /** @var list<Skill> */
    private array $skills = [];

    private ?Mana $mana = null;

    private bool $alive = true;

    private MapLayout $map;

    public function __construct(
        private readonly PlayerName $name,
    ) {
        $this->map = new MapLayout();
    }

    public function name(): PlayerName
    {
        return $this->name;
    }

    /** @throws LogicException */
    public function socket(): SocketId
    {
        return $this->socket ?? throw new LogicException("{$this->name->value} has not entered the game yet.");
    }

    /** @throws LogicException */
    public function position(): Position
    {
        return $this->position ?? throw new LogicException("{$this->name->value} is in no map yet.");
    }

    /** @throws LogicException */
    public function level(): PlayerLevel
    {
        return $this->level ?? throw new LogicException("The level of {$this->name->value} is not known yet.");
    }

    /** @return list<Skill> */
    public function skills(): array
    {
        return $this->skills;
    }

    public function mana(): ?Mana
    {
        return $this->mana;
    }

    public function alive(): bool
    {
        return $this->alive;
    }

    public function map(): MapLayout
    {
        return $this->map;
    }

    public function entered(InGame $game): void
    {
        $this->socket = $game->socketId;
        $this->position = new Position($game->map->map, $game->map->areaId);
        $this->notify();
    }

    public function hear(Event $event): void
    {
        $before = $this->state();
        $this->learn($event);

        if ($this->state() === $before) {
            return;
        }

        $this->notify();
    }

    private function learn(Event $event): void
    {
        match (true) {
            $event instanceof MovedToMapEvent => $this->position = new Position($event->map, $event->areaId),
            $event instanceof MonstersPlacedEvent => $this->map = new MapLayout($event->placements),
            $event instanceof PlayersPresentEvent => $this->seeLevel($event),
            $event instanceof LeveledUpEvent => $this->level = $event->level,
            $event instanceof SkillsLoadedEvent => $this->skills = array_values(array_filter(
                $event->skills,
                static fn(Skill $skill): bool => in_array($skill->slot, $event->usable, strict: true),
            )),
            $event instanceof PlayersManaChangedEvent => $this->seeMana($event),
            $event instanceof PlayerDiedEvent => $this->alive = false,
            $event instanceof ResurrectedEvent => $this->alive = true,
            default => null,
        };
    }

    /** @return list<mixed> */
    private function state(): array
    {
        return [
            $this->position?->map->value,
            $this->position?->area->value,
            $this->level?->value,
            $this->mana?->value,
            $this->alive,
            array_map(static fn(Skill $skill): string => $skill->slot->name, $this->skills),
            $this->map,
        ];
    }

    private function seeLevel(PlayersPresentEvent $event): void
    {
        foreach ($event->players as $player) {
            if ($player->level === null || !$this->is($player->name)) {
                continue;
            }

            $this->level = $player->level;
        }
    }

    private function seeMana(PlayersManaChangedEvent $event): void
    {
        foreach ($event->players as $player) {
            if (!$this->is($player->player)) {
                continue;
            }

            $this->mana = $player->mana;
        }
    }

    private function is(PlayerName $name): bool
    {
        return strcasecmp($name->value, $this->name->value) === 0;
    }
}
