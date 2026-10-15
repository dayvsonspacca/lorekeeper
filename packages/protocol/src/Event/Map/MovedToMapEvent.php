<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Event\FactEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\Message;
use Lorekeeper\Protocol\RoomId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class MovedToMapEvent implements FactEvent
{
    private const string HOUSE = 'house';

    /** @param list<Fact<MapName|MonsterId, object>> $facts */
    public function __construct(
        public AreaId $areaId,
        public RoomId $roomId,
        public MapName $mapName,
        public array $facts,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'moveToArea') {
            return Option\none();
        }

        $data = $message->data;
        foreach (['mondef', 'monBranch'] as $key) {
            if (is_array($data[$key] ?? null)) {
                continue;
            }

            $data[$key] = [];
        }

        $type = Type\shape([
            'areaId' => Type\positive_int(),
            'areaName' => Type\non_empty_string(),
            'strMapName' => Type\non_empty_string(),
            'strMapFileName' => Type\non_empty_string(),
            'mondef' => Type\vec(Type\shape([
                'MonID' => Type\positive_int(),
                'strMonName' => Type\non_empty_string(),
                'intLevel' => Type\positive_int(),
                'sRace' => Type\non_empty_string(),
                'strMonFileName' => Type\non_empty_string(),
                'strLinkage' => Type\non_empty_string(),
            ], allowUnknownFields: true)),
            'monBranch' => Type\vec(Type\shape([
                'MonID' => Type\positive_int(),
                'intHPMax' => Type\uint(),
                'intMPMax' => Type\uint(),
            ], allowUnknownFields: true)),
        ], allowUnknownFields: true);

        try {
            $data = $type->coerce($data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'map data is missing or invalid', $exception);
        }

        $mapName = new MapName($data['strMapName']);
        $facts = $mapName->value === self::HOUSE
            ? []
            : [
                new Fact($mapName, new Gamefile($data['strMapFileName'])),
                ...self::monsterFacts($mapName, $data['mondef']),
                ...self::spawnFacts($data['monBranch']),
            ];

        return Option\some(new static(new AreaId($data['areaId']), self::room($data['areaName']), $mapName, $facts));
    }

    private static function room(string $areaName): RoomId
    {
        $separator = strrpos($areaName, needle: '-');
        $room = $separator === false ? '' : substr($areaName, $separator + 1);

        if (!ctype_digit($room) || (int) $room < 1) {
            throw new MalformedEventException(static::class, "area name '{$areaName}' has no room number");
        }

        return new RoomId((int) $room);
    }

    /**
     * @param list<array{MonID: positive-int, strMonName: non-empty-string, intLevel: positive-int, sRace: non-empty-string, strMonFileName: non-empty-string, strLinkage: non-empty-string, ...}> $monsters
     *
     * @return list<Fact<MapName|MonsterId, object>>
     */
    private static function monsterFacts(MapName $mapName, array $monsters): array
    {
        $facts = [];

        foreach ($monsters as $monster) {
            $monsterId = new MonsterId($monster['MonID']);

            $facts[] = new Fact($mapName, new MapMonster($monsterId));
            $facts[] = new Fact($monsterId, new MonsterName($monster['strMonName']));
            $facts[] = new Fact($monsterId, new MonsterLevel($monster['intLevel']));
            $facts[] = new Fact($monsterId, new Gamefile($monster['strMonFileName']));
            $facts[] = new Fact($monsterId, new Linkage($monster['strLinkage']));

            $race = self::race($monster['sRace']);
            if ($race !== null) {
                $facts[] = new Fact($monsterId, $race);
            }
        }

        return $facts;
    }

    /**
     * @param list<array{MonID: positive-int, intHPMax: non-negative-int, intMPMax: non-negative-int, ...}> $spawns
     *
     * @return list<Fact<MonsterId, object>>
     */
    private static function spawnFacts(array $spawns): array
    {
        $facts = [];
        $seen = [];

        foreach ($spawns as $spawn) {
            if (array_key_exists($spawn['MonID'], $seen)) {
                continue;
            }

            $seen[$spawn['MonID']] = true;
            $monsterId = new MonsterId($spawn['MonID']);
            $facts[] = new Fact($monsterId, new Health($spawn['intHPMax']));
            $facts[] = new Fact($monsterId, new Mana($spawn['intMPMax']));
        }

        return $facts;
    }

    private static function race(string $race): ?Race
    {
        return match ($race) {
            'None' => Race::None,
            'Undead' => Race::Undead,
            'Human' => Race::Human,
            'Elemental' => Race::Elemental,
            'Dragonkin' => Race::Dragonkin,
            'Unknown' => Race::Unknown,
            default => null,
        };
    }

    #[Override]
    public function facts(): array
    {
        return $this->facts;
    }
}
