<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Fight;

use Lorekeeper\Client\Command\Combat\Attack;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Combat\Targetable;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use RuntimeException;

final readonly class Targets
{
    /**
     * @param non-empty-list<Targetable> $targets
     * @param array<int, true> $dead
     */
    private function __construct(
        private array $targets,
        private array $dead = [],
    ) {}

    /** @throws RuntimeException */
    public static function in(Map $map, MapCell $cell, Attack $attack): self
    {
        $aimed = $attack->target();
        if (!$aimed instanceof MonsterSpawnId) {
            return new self([$aimed]);
        }

        $here = array_values(array_filter(
            $map->monsters,
            static fn(MapMonster $monster): bool => $monster->cell->value === $cell->value,
        ));
        $kind = self::kindOf($aimed, $here) ?? throw new RuntimeException(
            "Monster {$aimed->value} does not stand in {$cell->value}.",
        );

        return new self([
            $aimed,
            ...array_values(array_map(
                static fn(MapMonster $monster): MonsterSpawnId => $monster->id,
                array_filter(
                    $here,
                    static fn(MapMonster $monster): bool => (
                        $monster->monster === $kind
                        && $monster->id->value !== $aimed->value
                    ),
                ),
            )),
        ]);
    }

    /** @param list<MapMonster> $here */
    private static function kindOf(MonsterSpawnId $spawn, array $here): ?Monster
    {
        foreach ($here as $monster) {
            if ($monster->id->value === $spawn->value) {
                return $monster->monster;
            }
        }

        return null;
    }

    public function next(): ?Targetable
    {
        foreach ($this->targets as $target) {
            if (!$target instanceof MonsterSpawnId || !array_key_exists($target->value, $this->dead)) {
                return $target;
            }
        }

        return null;
    }

    public function heard(null|Event|TimedOut $answer): self
    {
        $dead = $this->dead;

        if ($answer instanceof MonstersDiedEvent) {
            foreach ($answer->monsters as $monster) {
                $dead[$monster->value] = true;
            }
        }

        if ($answer instanceof MonsterRewardedEvent) {
            $dead[$answer->monster->value] = true;
        }

        if ($answer instanceof MonsterRespawnedEvent) {
            unset($dead[$answer->id->value]);
        }

        return new self($this->targets, $dead);
    }

    public function revived(): self
    {
        return new self($this->targets);
    }
}
