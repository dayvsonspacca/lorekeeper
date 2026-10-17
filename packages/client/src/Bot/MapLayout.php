<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot;

use Lorekeeper\Server\Event\Map\MapMonster;
use Lorekeeper\Server\Event\Map\MonsterPlacement;

final readonly class MapLayout
{
    /**
     * @param list<MonsterPlacement> $placements
     * @param list<MapMonster> $monsters
     */
    public function __construct(
        private array $placements = [],
        private array $monsters = [],
    ) {}

    /** @param list<MonsterPlacement> $placements */
    public function placed(array $placements): self
    {
        return new self($placements, $this->monsters);
    }

    /** @param list<MapMonster> $monsters */
    public function described(array $monsters): self
    {
        return new self($this->placements, $monsters);
    }

    /** @return list<MapCell> */
    public function cells(): array
    {
        $monsters = [];
        foreach ($this->monsters as $monster) {
            $monsters[$monster->id->value] = $monster;
        }

        $cells = [];
        $spawns = [];
        foreach ($this->placements as $placement) {
            $monster = $monsters[$placement->monster->value] ?? null;
            if ($monster === null) {
                continue;
            }

            $cells[$placement->cell->value] ??= $placement->cell;
            $spawns[$placement->cell->value][] = new Spawn($placement->id, $monster);
        }

        $layout = [];
        foreach ($cells as $name => $cell) {
            $layout[] = new MapCell($cell, $spawns[$name] ?? []);
        }

        return $layout;
    }
}
