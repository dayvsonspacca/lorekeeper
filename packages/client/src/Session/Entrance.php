<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Session;

use LogicException;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Location;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Client\Tracking\StatsTracking;
use Lorekeeper\Core\Game\Attribute\Stat\Stats;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Setting\GameSetting;
use Lorekeeper\Core\Item\Equipment\CharacterClass\CharacterClass;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Core\Player\Hero\Class\HeroSkill;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\Hero\HeroAttributes;
use Lorekeeper\Core\Player\Hero\HeroProgression;
use Lorekeeper\Core\Player\Hero\HeroSettings;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Player\Hero\Progression\LevelProgression;
use Lorekeeper\Core\Player\Hero\Progression\QuestsProgression;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\HeldItem;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerData;
use Lorekeeper\Server\Event\Player\PlayerDataLoadedEvent;
use Lorekeeper\Server\Event\Player\PlayerSettingsLoadedEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Event\Player\StatsUpdatedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;

/** @mago-expect lint:cyclomatic-complexity */
final class Entrance
{
    private ?PlayerSpawnId $spawnId = null;

    private ?MovedToMapEvent $arrival = null;

    /** @var ?list<HeldItem> */
    private ?array $inventory = null;

    private ?PlayerData $data = null;

    private ?ClassChangedEvent $class = null;

    private ?SkillsLoadedEvent $skills = null;

    private ?Vitals $vitals = null;

    private ?HeroSettings $settings = null;

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
            $event instanceof LoginSucceededEvent => $this->spawnId = $event->spawnId,
            $event instanceof MovedToMapEvent => $this->arrival = $event,
            $event instanceof InventoryLoadedEvent => $this->inventory = $event->items,
            $event instanceof PlayerDataLoadedEvent => $this->data = $this->mine($event) ?? $this->data,
            $event instanceof PlayerSettingsLoadedEvent => $this->settings =
                $this->settingsOf($event) ?? $this->settings,
            $event instanceof ClassChangedEvent => $this->isMine($event->player) ? ($this->class = $event) : null,
            $event instanceof SkillsLoadedEvent => $this->skills = $event,
            $event instanceof StatsUpdatedEvent => StatsTracking::follow($this->stats, $event),
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
            LoginSucceededEvent::class => $this->spawnId === null,
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
        $spawnId = $this->spawnId;
        $arrival = $this->arrival;
        $me = $this->me();
        $items = $this->inventory;
        $class = $this->class;
        $skills = $this->skills;
        $vitals = $this->vitals;
        $data = $this->data;

        if (
            $spawnId === null
            || $arrival === null
            || $me === null
            || $items === null
            || $class === null
            || $skills === null
            || $vitals === null
            || $data === null
        ) {
            throw new LogicException('Still waiting for ' . implode(', ', $this->missing()) . '.');
        }

        $inventory = new HeroInventory($data->bagSlots, $data->bankSlots, $data->wallet);
        InventoryTracking::loaded($inventory, $items);

        $hero = new Hero(
            $spawnId,
            $this->name,
            $data->level,
            new HeroAttributes($vitals, $this->stats),
            new HeroClass(
                new CharacterClass(
                    $class->className,
                    $class->category,
                    $class->description,
                    array_map(static fn(HeroSkill $skill): Skill => $skill->skill, $skills->skills),
                    $skills->passives,
                ),
                $class->points,
                $skills->skills,
            ),
            new HeroProgression(new LevelProgression($data->experience), new QuestsProgression($data->quests)),
            $inventory,
            $this->settings ?? new HeroSettings(GameSetting::cases()),
        );
        if ($data->member) {
            $hero->tags->add(PlayerTag::Member);
        }

        return new Character($hero, new Location($arrival->areaId, $arrival->map, $me->spot), $me->state, away: false);
    }

    private function me(): ?MapPlayer
    {
        $spawnId = $this->spawnId;
        if ($spawnId === null) {
            return null;
        }

        return $this->arrival
            ?->map
            ->players
            ->find($spawnId)
            ->unwrapOr(null);
    }

    private function mine(PlayerDataLoadedEvent $event): ?PlayerData
    {
        foreach ($event->players as $player) {
            if ($this->isMine($player->spawnId)) {
                return $player;
            }
        }

        return null;
    }

    private function settingsOf(PlayerSettingsLoadedEvent $event): ?HeroSettings
    {
        foreach ($event->players as $player) {
            if ($this->isMine($player->spawnId)) {
                return $player->settings;
            }
        }

        return null;
    }

    private function isMine(PlayerSpawnId $spawnId): bool
    {
        return $this->spawnId?->equals($spawnId) ?? false;
    }

    private function isMe(PlayerName $name): bool
    {
        return strcasecmp($name->value, $this->name->value) === 0;
    }
}
