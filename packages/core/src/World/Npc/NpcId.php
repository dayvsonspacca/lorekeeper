<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Npc;

use InvalidArgumentException;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Map\MapName;
use Override;

final readonly class NpcId implements Subject
{
    public function __construct(
        public MapName $map,
        public NpcName $name,
    ) {}

    /** @throws InvalidArgumentException */
    public static function fromString(string $id): self
    {
        $parts = explode('/', $id, limit: 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException("NPC id must look like 'map/Name', got '{$id}'.");
        }

        return new self(new MapName($parts[0]), new NpcName($parts[1]));
    }

    #[Override]
    public function __toString(): string
    {
        return "{$this->map->value}/{$this->name->value}";
    }
}
