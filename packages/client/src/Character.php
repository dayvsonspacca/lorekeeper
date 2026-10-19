<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerClass;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\CharacterClass\ClassChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerVitalsUpdatedEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\SocketId;
use Psl\Observer\SubjectInterface;

/**
 * @mago-expect lint:too-many-methods
 * @mago-expect lint:kan-defect
 */
final class Character implements SubjectInterface
{
    use Observable;

    /** @var list<SkillSlot> */
    private array $usable;

    private ?ClassChangedEvent $changingClass = null;

    /**
     * @param list<SkillSlot> $usable
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        private Player $player,
        private readonly SocketId $socketId,
        private AreaId $area,
        private Map $map,
        private MapSpot $spot,
        private CombatState $state,
        private bool $away,
        array $usable,
    ) {
        $this->usable = $usable;
    }

    public function player(): Player
    {
        return $this->player;
    }

    public function socketId(): SocketId
    {
        return $this->socketId;
    }

    public function area(): AreaId
    {
        return $this->area;
    }

    public function map(): Map
    {
        return $this->map;
    }

    public function spot(): MapSpot
    {
        return $this->spot;
    }

    public function state(): CombatState
    {
        return $this->state;
    }

    public function away(): bool
    {
        return $this->away;
    }

    /** @return list<Skill> */
    public function usableSkills(): array
    {
        return array_values(array_filter($this->player->class->class->skills, fn(Skill $skill): bool => in_array(
            $skill->slot,
            $this->usable,
            strict: true,
        )));
    }

    public function hear(Event $event): void
    {
        $before = $this->snapshot();
        $this->learn($event);

        if ($this->snapshot() === $before) {
            return;
        }

        $this->notify();
    }

    private function learn(Event $event): void
    {
        match (true) {
            $event instanceof MovedToMapEvent => $this->arrived($event),
            $event instanceof PlayerVitalsUpdatedEvent => $this->vitalsUpdated($event),
            $event instanceof PlayersHealthChangedEvent => $this->healthChanged($event),
            $event instanceof PlayersManaChangedEvent => $this->manaChanged($event),
            $event instanceof PlayerDiedEvent => $this->died(),
            $event instanceof ResurrectedEvent => $this->resurrected(),
            $event instanceof LeveledUpEvent => $this->leveled($event->level),
            $event instanceof ClassChangedEvent => $this->classChanging($event),
            $event instanceof SkillsLoadedEvent => $this->skillsLoaded($event),
            $event instanceof PlayerInventoryLoadedEvent => $this->inventory($event->inventory),
            default => null,
        };
    }

    /** @return list<mixed> */
    private function snapshot(): array
    {
        $player = $this->player;
        $items = [];
        foreach ($player->inventory->items as $id => $held) {
            $items[$id] = $held->quantity->value;
        }

        return [
            $player->level->value,
            $player->vitals->health->current->value,
            $player->vitals->health->maximum->value,
            $player->vitals->mana->current->value,
            $player->vitals->mana->maximum->value,
            $player->class->class->name->value,
            $player->class->points->value,
            array_map(static fn(Skill $skill): string => $skill->slot->name, $player->class->class->skills),
            $items,
            $this->area->value,
            $this->map->name->value,
            $this->spot->cell->value,
            $this->spot->pad,
            $this->state,
            $this->away,
            $this->usable,
        ];
    }

    private function vitalsUpdated(PlayerVitalsUpdatedEvent $event): void
    {
        if (!$this->is($event->name)) {
            return;
        }

        $this->vitals($event->vitals);
    }

    private function classChanging(ClassChangedEvent $event): void
    {
        if ($event->player->value !== $this->socketId->value) {
            return;
        }

        $this->changingClass = $event;
    }

    private function arrived(MovedToMapEvent $event): void
    {
        $this->area = $event->areaId;
        $this->map = $event->map;

        foreach ($event->players as $seen) {
            if ($seen->socketId->value !== $this->socketId->value) {
                continue;
            }

            $this->spot = $seen->player->spot;
            $this->state = $seen->player->state;
            $this->away = $seen->player->away;
        }
    }

    private function healthChanged(PlayersHealthChangedEvent $event): void
    {
        foreach ($event->players as $player) {
            if (!$this->is($player->player)) {
                continue;
            }

            $this->health($player->health);
        }
    }

    private function manaChanged(PlayersManaChangedEvent $event): void
    {
        foreach ($event->players as $player) {
            if (!$this->is($player->player)) {
                continue;
            }

            $this->mana($player->mana);
        }
    }

    private function died(): void
    {
        $this->state = CombatState::Dead;
        $this->health(new Health(0));
    }

    private function resurrected(): void
    {
        $this->state = CombatState::Idle;
        $this->health($this->player->vitals->health->maximum);
    }

    private function health(Health $health): void
    {
        $maximum = $this->player->vitals->health->maximum;

        $this->vitals(
            new Vitals(
                new Gauge($health, $health->value > $maximum->value ? $health : $maximum),
                $this->player->vitals->mana,
            ),
        );
    }

    private function mana(Mana $mana): void
    {
        $maximum = $this->player->vitals->mana->maximum;

        $this->vitals(
            new Vitals(
                $this->player->vitals->health,
                new Gauge($mana, $mana->value > $maximum->value ? $mana : $maximum),
            ),
        );
    }

    private function vitals(Vitals $vitals): void
    {
        $player = $this->player;
        $this->player = new Player($player->name, $player->level, $vitals, $player->class, $player->inventory);
    }

    private function leveled(PlayerLevel $level): void
    {
        $player = $this->player;
        $this->player = new Player($player->name, $level, $player->vitals, $player->class, $player->inventory);
    }

    private function inventory(PlayerInventory $inventory): void
    {
        $player = $this->player;
        $this->player = new Player($player->name, $player->level, $player->vitals, $player->class, $inventory);
    }

    private function skillsLoaded(SkillsLoadedEvent $event): void
    {
        $player = $this->player;
        $changing = $this->changingClass;
        $current = $player->class;

        $class = $changing === null
            ? new PlayerClass(
                new CharacterClass(
                    $current->class->name,
                    $current->class->category,
                    $current->class->description,
                    $event->skills,
                ),
                $current->points,
            )
            : new PlayerClass(
                new CharacterClass($changing->className, $changing->category, $changing->description, $event->skills),
                $changing->points,
            );

        $this->changingClass = null;
        $this->usable = $event->usable;
        $this->player = new Player($player->name, $player->level, $player->vitals, $class, $player->inventory);
    }

    private function is(PlayerName $name): bool
    {
        return strcasecmp($name->value, $this->player->name->value) === 0;
    }
}
