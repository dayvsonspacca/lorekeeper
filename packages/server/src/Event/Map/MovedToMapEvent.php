<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\RoomId;
use Override;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class MovedToMapEvent extends MapArrivalEvent
{
    public function __construct(
        public AreaId $areaId,
        public RoomId $roomId,
        public MapName $map,
        public Gamefile $file,
    ) {}

    #[Override]
    public function summary(): string
    {
        return "{$this->map->value} (room {$this->roomId->value})";
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
            ], allowUnknownFields: true)->coerce($data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'map data is missing or invalid', $exception);
        }

        return new static(
            new AreaId($data['areaId']),
            self::room($data['areaName']),
            new MapName($data['strMapName']),
            new Gamefile($data['strMapFileName']),
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
}
