<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Reward;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Exception\MalformedEventException;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * Our player earned gold, experience or class points for killing a monster.
 */
final readonly class MonsterRewardedEvent extends RewardedEvent
{
    public function __construct(
        Gold $gold,
        Experience $experience,
        ClassPoints $classPoints,
        public MonsterSpawnId $monster,
    ) {
        parent::__construct($gold, $experience, $classPoints);
    }

    #[Override]
    protected static function read(
        array $data,
        Gold $gold,
        Experience $experience,
        ClassPoints $classPoints,
    ): Option\Option {
        if (($data['typ'] ?? null) !== 'm') {
            return Option\none();
        }

        try {
            $monster = Type\positive_int()->coerce($data['id'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'rewarding monster is missing or invalid', $exception);
        }

        return Option\some(new static($gold, $experience, $classPoints, new MonsterSpawnId($monster)));
    }

    #[Override]
    public function summary(): string
    {
        return "Earned {$this->experience->value} experience and {$this->gold->value} gold from monster {$this->monster->value}";
    }
}
