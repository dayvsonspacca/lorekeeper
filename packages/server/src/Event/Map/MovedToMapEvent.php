<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use InvalidArgumentException;
use Lorekeeper\Core\Game\Asset\Gamefile;
use Lorekeeper\Core\Game\Asset\Linkage;
use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapMonsters;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapPlayers;
use Lorekeeper\Core\World\Map\MapRoom;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterAppearance;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\Helper\CombatStates;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * Our player arrived in a map, with the monsters and players already in the room.
 *
 * @psalm-type Arrival = array{
 *     areaId: positive-int,
 *     areaName: non-empty-string,
 *     strMapName: non-empty-string,
 *     strMapFileName: non-empty-string,
 *     mondef?: list<array{MonID: positive-int, strMonName: non-empty-string, intLevel: positive-int, sRace: string, strMonFileName: non-empty-string, strLinkage: non-empty-string, ...}>,
 *     monmap?: list<array{MonMapID: positive-int, MonID: positive-int, strFrame: non-empty-string, ...}>,
 *     monBranch?: list<array{MonMapID: positive-int, intState: int<0, 2>, intHP: non-negative-int, intHPMax: non-negative-int, intMP: non-negative-int, intMPMax: non-negative-int, ...}>,
 *     uoBranch?: list<array{entID: positive-int, strUsername: non-empty-string, intLevel: int<1, 100>, strFrame: non-empty-string, strPad: non-empty-string, intState: int<0, 2>, intHP: non-negative-int, intHPMax: non-negative-int, intMP: non-negative-int, intMPMax: non-negative-int, ...}>,
 *     ...
 * }
 *
 * @mago-expect lint:cyclomatic-complexity
 */
final readonly class MovedToMapEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public AreaId $areaId,
        public Map $map,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'moveToArea') {
            return Option\none();
        }

        $data = self::arrival($message->data);

        try {
            $map = new Map(
                new MapName($data['strMapName']),
                self::room($data['areaName']),
                new Gamefile($data['strMapFileName']),
                new MapMonsters(self::monsters($data)),
                new MapPlayers(self::players($data)),
            );
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(
                static::class,
                'a monster or player is in the room more than once',
                $exception,
            );
        }

        return Option\some(new static(new AreaId($data['areaId']), $map));
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->map->name->value} (room {$this->map->room->value})";
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws MalformedEventException
     *
     * @return Arrival
     */
    private static function arrival(array $data): array
    {
        $vitals = [
            'intState' => Type\int_range(min: 0, max: 2),
            'intHP' => Type\uint(),
            'intHPMax' => Type\uint(),
            'intMP' => Type\uint(),
            'intMPMax' => Type\uint(),
        ];

        try {
            return Type\shape([
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
                'monmap' => Type\optional(Type\vec(Type\shape([
                    'MonMapID' => Type\positive_int(),
                    'MonID' => Type\positive_int(),
                    'strFrame' => Type\non_empty_string(),
                ], allowUnknownFields: true))),
                'monBranch' => Type\optional(Type\vec(Type\shape([
                    'MonMapID' => Type\positive_int(),
                    ...$vitals,
                ], allowUnknownFields: true))),
                'uoBranch' => Type\optional(Type\vec(Type\shape([
                    'entID' => Type\positive_int(),
                    'strUsername' => Type\non_empty_string(),
                    'intLevel' => Type\int_range(min: 1, max: 100),
                    'strFrame' => Type\non_empty_string(),
                    'strPad' => Type\non_empty_string(),
                    ...$vitals,
                ], allowUnknownFields: true))),
            ], allowUnknownFields: true)->coerce($data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'map data is missing or invalid', $exception);
        }
    }

    /**
     * @param Arrival $data
     *
     * @throws MalformedEventException
     *
     * @return list<MapMonster>
     */
    private static function monsters(array $data): array
    {
        $kinds = [];
        foreach ($data['mondef'] ?? [] as $kind) {
            $kinds[$kind['MonID']] = new Monster(
                new MonsterName($kind['strMonName']),
                new MonsterLevel($kind['intLevel']),
                self::race($kind['sRace']),
                new MonsterAppearance(new Gamefile($kind['strMonFileName']), new Linkage($kind['strLinkage'])),
            );
        }

        $seen = [];
        foreach ($data['monBranch'] ?? [] as $spawn) {
            $seen[$spawn['MonMapID']] = $spawn;
        }

        $monsters = [];
        foreach ($data['monmap'] ?? [] as $placement) {
            $kind = $kinds[$placement['MonID']] ?? null;
            $spawn = $seen[$placement['MonMapID']] ?? null;
            if ($kind === null || $spawn === null) {
                continue;
            }

            $monsters[] = new MapMonster(
                new MonsterSpawnId($placement['MonMapID']),
                $kind,
                new MapCell($placement['strFrame']),
                self::vitals($spawn),
                CombatStates::read($spawn['intState']),
            );
        }

        return $monsters;
    }

    /**
     * @param Arrival $data
     *
     * @throws MalformedEventException
     *
     * @return list<MapPlayer>
     */
    private static function players(array $data): array
    {
        return array_map(
            static fn(array $player): MapPlayer => new MapPlayer(
                new Player(
                    new PlayerSpawnId($player['entID']),
                    new PlayerName($player['strUsername']),
                    new PlayerLevel($player['intLevel']),
                    self::vitals($player),
                ),
                new MapSpot(new MapCell($player['strFrame']), self::pad($player['strPad'])),
                CombatStates::read($player['intState']),
            ),
            $data['uoBranch'] ?? [],
        );
    }

    /**
     * @param array{intHP: non-negative-int, intHPMax: non-negative-int, intMP: non-negative-int, intMPMax: non-negative-int, ...} $someone
     *
     * @throws MalformedEventException
     */
    private static function vitals(array $someone): Vitals
    {
        try {
            return new Vitals(
                new Gauge(new Health($someone['intHP']), new Health($someone['intHPMax'])),
                new Gauge(new Mana($someone['intMP']), new Mana($someone['intMPMax'])),
            );
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(static::class, 'health or mana is above its maximum', $exception);
        }
    }

    /** @throws MalformedEventException */
    private static function room(string $areaName): MapRoom
    {
        $separator = strrpos($areaName, needle: '-');
        $room = $separator === false ? '' : substr($areaName, $separator + 1);

        if (!ctype_digit($room) || (int) $room < 1) {
            throw new MalformedEventException(static::class, "area name '{$areaName}' has no room number");
        }

        return new MapRoom((int) $room);
    }

    /** @throws MalformedEventException */
    private static function race(string $race): MonsterRace
    {
        return match ($race) {
            'None' => MonsterRace::None,
            'Undead' => MonsterRace::Undead,
            'Human' => MonsterRace::Human,
            'Elemental' => MonsterRace::Elemental,
            'Dragonkin' => MonsterRace::Dragonkin,
            'Chaos' => MonsterRace::Chaos,
            'Unknown' => MonsterRace::Unknown,
            default => throw new MalformedEventException(static::class, "unknown race '{$race}'"),
        };
    }

    /** @throws MalformedEventException */
    private static function pad(string $pad): MapPad
    {
        return match (strtolower($pad)) {
            'left' => MapPad::Left,
            'right' => MapPad::Right,
            'top' => MapPad::Top,
            'bottom' => MapPad::Bottom,
            'up' => MapPad::Up,
            'down' => MapPad::Down,
            'center' => MapPad::Center,
            'spawn' => MapPad::Spawn,
            default => throw new MalformedEventException(static::class, "unknown pad '{$pad}'"),
        };
    }
}
