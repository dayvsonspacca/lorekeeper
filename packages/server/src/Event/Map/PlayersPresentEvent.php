<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Exception\MalformedEventException;
use Override;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayersPresentEvent extends MoveToAreaEvent
{
    /** @param list<MapPlayer> $players */
    public function __construct(
        public array $players,
    ) {}

    #[Override]
    protected static function read(array $data): static
    {
        try {
            $players = Type\shape([
                'uoBranch' => Type\vec(Type\shape([
                    'strUsername' => Type\non_empty_string(),
                    'intLevel' => Type\optional(Type\int_range(min: 1, max: 100)),
                ], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($data)['uoBranch'];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'players are missing or invalid', $exception);
        }

        return new static(array_map(
            static fn(array $player): MapPlayer => new MapPlayer(
                new PlayerName($player['strUsername']),
                array_key_exists('intLevel', $player) ? new PlayerLevel($player['intLevel']) : null,
            ),
            $players,
        ));
    }
}
