<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Damage;

use InvalidArgumentException;

final readonly class DamageMultiplier
{
    /** @throws InvalidArgumentException */
    public function __construct(
        public float $value,
    ) {
        if ($value <= 0.0) {
            throw new InvalidArgumentException("Damage multiplier {$value} is not above zero.");
        }
    }
}
