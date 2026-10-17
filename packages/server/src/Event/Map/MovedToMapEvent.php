<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Mention\Mention;
use Lorekeeper\Server\Event\Mention\Mentions;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\RoomId;
use Override;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class MovedToMapEvent extends MapArrivalEvent implements Mentions, MapJoinedEvent
{
    /**
     * @param list<MonsterId> $monsters
     * @param list<PlayerName> $players
     * @param list<Mention<MonsterId, object>> $mentions
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public AreaId $areaId,
        public RoomId $roomId,
        public MapName $map,
        public Gamefile $file,
        public array $monsters,
        public array $players,
        private array $mentions,
    ) {}

    /** @return list<Mention<MonsterId, object>> */
    #[Override]
    public function mentions(): array
    {
        return $this->mentions;
    }

    #[Override]
    protected static function read(array $data): static
    {
        try {
            $data = Type\shape([
                'areaId' => Type\positive_int(),
                'areaName' => Type\non_empty_string(),
                'strMapName' => Type\non_empty_string(),
                'strMapFileName' => Type\non_empty_string(),
                'mondef' => Type\optional(Type\vec(Type\shape([
                    'MonID' => Type\positive_int(),
                    'strMonName' => Type\non_empty_string(),
                    'intLevel' => Type\positive_int(),
                    'sRace' => Type\string(),
                    'strMonFileName' => Type\non_empty_string(),
                    'strLinkage' => Type\non_empty_string(),
                ], allowUnknownFields: true))),
                'uoBranch' => Type\vec(Type\shape([
                    'strUsername' => Type\non_empty_string(),
                ], allowUnknownFields: true)),
                'monBranch' => Type\vec(Type\shape([
                    'MonID' => Type\positive_int(),
                    'intHPMax' => Type\uint(),
                    'intMPMax' => Type\uint(),
                ], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'map data is missing or invalid', $exception);
        }

        $spawns = [];
        foreach ($data['monBranch'] as $spawn) {
            $spawns[$spawn['MonID']] ??= $spawn;
        }

        $monsters = [];
        $mentions = [];
        foreach ($data['mondef'] ?? [] as $monster) {
            $id = new MonsterId($monster['MonID']);
            $monsters[] = $id;
            $values = [
                new MonsterName($monster['strMonName']),
                new MonsterLevel($monster['intLevel']),
                self::race($monster['sRace']),
                new Gamefile($monster['strMonFileName']),
                new Linkage($monster['strLinkage']),
            ];

            $spawn = $spawns[$monster['MonID']] ?? null;
            if ($spawn !== null) {
                $values[] = new Health($spawn['intHPMax']);
                $values[] = new Mana($spawn['intMPMax']);
            }

            foreach ($values as $value) {
                $mentions[] = new Mention($id, $value);
            }
        }

        return new static(
            new AreaId($data['areaId']),
            self::room($data['areaName']),
            new MapName($data['strMapName']),
            new Gamefile($data['strMapFileName']),
            $monsters,
            array_map(
                static fn(array $player): PlayerName => new PlayerName($player['strUsername']),
                $data['uoBranch'],
            ),
            $mentions,
        );
    }

    /** @throws MalformedEventException */
    private static function room(string $areaName): RoomId
    {
        $separator = strrpos($areaName, needle: '-');
        $room = $separator === false ? '' : substr($areaName, $separator + 1);

        if (!ctype_digit($room) || (int) $room < 1) {
            throw new MalformedEventException(static::class, "area name '{$areaName}' has no room number");
        }

        return new RoomId((int) $room);
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
