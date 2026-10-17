<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Exception\MalformedEventException;
use Override;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class MonstersSeenEvent extends MapArrivalEvent
{
    /** @param list<SeenMonster> $monsters */
    public function __construct(
        public array $monsters,
    ) {}

    #[Override]
    public function summary(): string
    {
        $alive = count(array_filter(
            $this->monsters,
            static fn(SeenMonster $monster): bool => $monster->state !== CombatState::Dead,
        ));

        return "{$alive} of " . count($this->monsters) . ' monsters are alive';
    }

    #[Override]
    protected static function read(array $data): static
    {
        try {
            $monsters = Type\shape([
                'monBranch' => Type\optional(Type\vec(Type\shape([
                    'MonMapID' => Type\positive_int(),
                    'intState' => Type\int_range(min: 0, max: 2),
                    'intHP' => Type\uint(),
                    'intHPMax' => Type\uint(),
                    'intMP' => Type\uint(),
                    'intMPMax' => Type\uint(),
                ], allowUnknownFields: true))),
            ], allowUnknownFields: true)->coerce($data)['monBranch'] ?? [];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'monsters are invalid', $exception);
        }

        return new static(array_map(
            static fn(array $monster): SeenMonster => new SeenMonster(
                new MonsterSpawnId($monster['MonMapID']),
                self::state($monster['intState']),
                new Health($monster['intHP']),
                new Health($monster['intHPMax']),
                new Mana($monster['intMP']),
                new Mana($monster['intMPMax']),
            ),
            $monsters,
        ));
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
