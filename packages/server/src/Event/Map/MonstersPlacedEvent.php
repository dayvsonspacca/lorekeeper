<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Exception\MalformedEventException;
use Override;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class MonstersPlacedEvent extends MapArrivalEvent
{
    /** @param list<MonsterPlacement> $placements */
    public function __construct(
        public array $placements,
    ) {}

    #[Override]
    protected static function read(array $data): static
    {
        try {
            $data = Type\shape([
                'mondef' => Type\optional(Type\vec(Type\shape([
                    'MonID' => Type\positive_int(),
                    'intLevel' => Type\positive_int(),
                ], allowUnknownFields: true))),
                'monmap' => Type\optional(Type\vec(Type\shape([
                    'MonMapID' => Type\positive_int(),
                    'MonID' => Type\positive_int(),
                    'strFrame' => Type\non_empty_string(),
                ], allowUnknownFields: true))),
            ], allowUnknownFields: true)->coerce($data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'monster placements are invalid', $exception);
        }

        $levels = [];
        foreach ($data['mondef'] ?? [] as $monster) {
            $levels[$monster['MonID']] = new MonsterLevel($monster['intLevel']);
        }

        return new static(array_map(
            static fn(array $placement): MonsterPlacement => new MonsterPlacement(
                new MonsterSpawnId($placement['MonMapID']),
                new MonsterId($placement['MonID']),
                new Cell($placement['strFrame']),
                $levels[$placement['MonID']] ?? null,
            ),
            $data['monmap'] ?? [],
        ));
    }
}
