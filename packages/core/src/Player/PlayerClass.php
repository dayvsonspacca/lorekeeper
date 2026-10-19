<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Rank;

final readonly class PlayerClass
{
    /** @param list<PlayerSkill> $skills */
    public function __construct(
        public CharacterClass $class,
        public ClassPoints $points,
        public array $skills,
    ) {}

    public function rank(): Rank
    {
        return Rank::fromPoints($this->points);
    }
}
