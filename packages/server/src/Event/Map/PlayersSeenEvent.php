<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Server\Exception\MalformedEventException;
use Override;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayersSeenEvent extends MapArrivalEvent
{
    /** @param list<SeenPlayer> $players */
    public function __construct(
        public array $players,
    ) {}

    #[Override]
    public function summary(): string
    {
        return count($this->players) . ' players are in the map';
    }

    #[Override]
    protected static function read(array $data): static
    {
        try {
            $players = Type\shape([
                'uoBranch' => Type\vec(Type\shape([
                    'strUsername' => Type\non_empty_string(),
                    'intLevel' => Type\int_range(min: 1, max: 100),
                    'strFrame' => Type\non_empty_string(),
                    'strPad' => Type\non_empty_string(),
                    'intState' => Type\int_range(min: 0, max: 2),
                    'intHP' => Type\uint(),
                    'intHPMax' => Type\uint(),
                    'intMP' => Type\uint(),
                    'intMPMax' => Type\uint(),
                    'afk' => Type\bool(),
                ], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($data)['uoBranch'];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'players are missing or invalid', $exception);
        }

        return new static(array_map(
            static fn(array $player): SeenPlayer => new SeenPlayer(
                new PlayerName($player['strUsername']),
                new PlayerLevel($player['intLevel']),
                new MapCell($player['strFrame']),
                self::pad($player['strPad']),
                self::state($player['intState']),
                new Health($player['intHP']),
                new Health($player['intHPMax']),
                new Mana($player['intMP']),
                new Mana($player['intMPMax']),
                $player['afk'],
            ),
            $players,
        ));
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

    private static function state(int $state): CombatState
    {
        return match ($state) {
            0 => CombatState::Dead,
            2 => CombatState::Fighting,
            default => CombatState::Idle,
        };
    }
}
