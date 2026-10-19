<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerEnteredEvent;
use Lorekeeper\Server\Event\Player\PlayerLeftEvent;

/** @mago-expect lint:kan-defect */
final readonly class Room
{
    /**
     * @param array<int, MapMonster> $monsters
     * @param array<int, int> $struck
     * @param list<string> $players
     */
    public function __construct(
        public array $monsters = [],
        public array $struck = [],
        public array $players = [],
    ) {}

    public static function of(Map $map): self
    {
        $monsters = [];
        foreach ($map->monsters as $monster) {
            $monsters[$monster->id->value] = $monster;
        }

        return new self($monsters);
    }

    public function monster(MonsterSpawnId $id): ?MapMonster
    {
        return $this->monsters[$id->value] ?? null;
    }

    public function heard(Event $event, PlayerName $me, int $now): self
    {
        return match (true) {
            $event instanceof MovedToMapEvent => self::arrived($event, $me),
            $event instanceof MonstersHealthChangedEvent => $this->struck($event, $now),
            $event instanceof MonstersDiedEvent => $this->died($event),
            $event instanceof MonsterRespawnedEvent => $this->respawned($event),
            $event instanceof PlayerEnteredEvent => $this->entered($event->name, $me),
            $event instanceof PlayerLeftEvent => new self(
                $this->monsters,
                $this->struck,
                array_values(array_filter(
                    $this->players,
                    static fn(string $name): bool => strcasecmp($name, $event->name->value) !== 0,
                )),
            ),
            default => $this,
        };
    }

    private static function arrived(MovedToMapEvent $event, PlayerName $me): self
    {
        $room = self::of($event->map);
        foreach ($event->players as $seen) {
            $room = $room->entered($seen->player->name, $me);
        }

        return $room;
    }

    private function entered(PlayerName $name, PlayerName $me): self
    {
        foreach ([$me->value, ...$this->players] as $known) {
            if (strcasecmp($known, $name->value) === 0) {
                return $this;
            }
        }

        return new self($this->monsters, $this->struck, [...$this->players, $name->value]);
    }

    private function struck(MonstersHealthChangedEvent $event, int $now): self
    {
        $monsters = $this->monsters;
        $struck = $this->struck;
        foreach ($event->monsters as $changed) {
            $monster = $monsters[$changed->monster->value] ?? null;
            if ($monster === null) {
                continue;
            }

            $maximum = $monster->vitals->health->maximum;
            $health = new Health(min($changed->health->value, $maximum->value));
            $monsters[$changed->monster->value] = self::with(
                $monster,
                $health,
                $health->value === 0 ? CombatState::Dead : CombatState::Fighting,
            );
            $struck[$changed->monster->value] = $now;
        }

        return new self($monsters, $struck, $this->players);
    }

    private function died(MonstersDiedEvent $event): self
    {
        $monsters = $this->monsters;
        foreach ($event->monsters as $id) {
            $monster = $monsters[$id->value] ?? null;
            if ($monster !== null) {
                $monsters[$id->value] = self::with($monster, new Health(0), CombatState::Dead);
            }
        }

        return new self($monsters, $this->struck, $this->players);
    }

    private function respawned(MonsterRespawnedEvent $event): self
    {
        $monster = $this->monsters[$event->id->value] ?? null;
        if ($monster === null) {
            return $this;
        }

        $monsters = $this->monsters;
        $monsters[$event->id->value] = self::with($monster, $monster->vitals->health->maximum, CombatState::Idle);
        $struck = $this->struck;
        unset($struck[$event->id->value]);

        return new self($monsters, $struck, $this->players);
    }

    private static function with(MapMonster $monster, Health $health, CombatState $state): MapMonster
    {
        return new MapMonster(
            $monster->id,
            $monster->monster,
            $monster->cell,
            new Vitals(new Gauge($health, $monster->vitals->health->maximum), $monster->vitals->mana),
            $state,
        );
    }
}
