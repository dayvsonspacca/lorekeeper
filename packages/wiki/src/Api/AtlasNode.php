<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Api;

use JsonSerializable;
use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapUnavailable;
use Lorekeeper\Core\World\Region\RegionMap;
use Override;

final class AtlasNode implements JsonSerializable
{
    private ?string $title = null;

    private ?string $description = null;

    private ?int $minLevel = null;

    private ?int $maxLevel = null;

    private ?int $mapId = null;

    private bool $visited = false;

    private bool $memberOnly = false;

    private bool $unavailable = false;

    private int $monsters = 0;

    /** @var list<string> */
    private array $regions = [];

    public function __construct(
        private readonly string $id,
    ) {}

    public function learn(object $value): void
    {
        $this->visited = $this->visited || $value instanceof Gamefile;
        $this->memberOnly = $this->memberOnly || $value instanceof MemberOnly;
        $this->unavailable = $this->unavailable || $value instanceof MapUnavailable;
        $this->monsters += $value instanceof MapMonster ? 1 : 0;
        $this->mapId = $value instanceof MapId ? $value->value : $this->mapId;
    }

    public function listIn(string $region, RegionMap $entry): void
    {
        $this->title ??= $entry->title->value;
        $this->description ??= $entry
            ->description
            ->map(static fn(MapDescription $d): string => $d->value)
            ->unwrapOr(null);
        $this->minLevel ??= $entry->levels->minimum;
        $this->maxLevel ??= $entry->levels->maximum;
        $this->regions[] = $region;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'minLevel' => $this->minLevel,
            'maxLevel' => $this->maxLevel,
            'mapId' => $this->mapId,
            'visited' => $this->visited,
            'memberOnly' => $this->memberOnly,
            'unavailable' => $this->unavailable,
            'monsters' => $this->monsters,
            'regions' => $this->regions,
        ];
    }
}
