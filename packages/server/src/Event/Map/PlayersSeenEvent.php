<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use InvalidArgumentException;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Gauge;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\SocketId;
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
                    'entID' => Type\positive_int(),
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
                new SocketId($player['entID']),
                new MapPlayer(
                    new PlayerName($player['strUsername']),
                    new PlayerLevel($player['intLevel']),
                    self::vitals($player['intHP'], $player['intHPMax'], $player['intMP'], $player['intMPMax']),
                    new MapSpot(new MapCell($player['strFrame']), self::pad($player['strPad'])),
                    self::state($player['intState']),
                    $player['afk'],
                ),
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

    /** @throws MalformedEventException */
    private static function vitals(int $health, int $maxHealth, int $mana, int $maxMana): Vitals
    {
        try {
            return new Vitals(
                new Gauge(new Health($health), new Health($maxHealth)),
                new Gauge(new Mana($mana), new Mana($maxMana)),
            );
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(
                static::class,
                'a player has more health or mana than its maximum',
                $exception,
            );
        }
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
