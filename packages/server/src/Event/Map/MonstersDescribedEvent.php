<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Server\Exception\MalformedEventException;
use Override;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class MonstersDescribedEvent extends MapArrivalEvent
{
    /** @param list<MapMonster> $monsters */
    public function __construct(
        public array $monsters,
    ) {}

    #[Override]
    public function summary(): string
    {
        return count($this->monsters) . ' kinds of monsters live in the map';
    }

    #[Override]
    protected static function read(array $data): static
    {
        try {
            $monsters = Type\shape([
                'mondef' => Type\optional(Type\vec(Type\shape([
                    'MonID' => Type\positive_int(),
                    'strMonName' => Type\non_empty_string(),
                    'intLevel' => Type\positive_int(),
                    'sRace' => Type\string(),
                    'strMonFileName' => Type\non_empty_string(),
                    'strLinkage' => Type\non_empty_string(),
                ], allowUnknownFields: true))),
            ], allowUnknownFields: true)->coerce($data)['mondef'] ?? [];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'monsters are invalid', $exception);
        }

        return new static(array_map(
            static fn(array $monster): MapMonster => new MapMonster(
                new MonsterId($monster['MonID']),
                new MonsterName($monster['strMonName']),
                new MonsterLevel($monster['intLevel']),
                self::race($monster['sRace']),
                new Gamefile($monster['strMonFileName']),
                new Linkage($monster['strLinkage']),
            ),
            $monsters,
        ));
    }

    /** @throws MalformedEventException */
    private static function race(string $race): Race
    {
        return match ($race) {
            'None' => Race::None,
            'Undead' => Race::Undead,
            'Human' => Race::Human,
            'Elemental' => Race::Elemental,
            'Dragonkin' => Race::Dragonkin,
            'Unknown' => Race::Unknown,
            default => throw new MalformedEventException(static::class, "unknown race '{$race}'"),
        };
    }
}
