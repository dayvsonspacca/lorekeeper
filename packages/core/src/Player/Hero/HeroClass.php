<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\CharacterClass\ClassPoints;
use Lorekeeper\Core\Game\Rank\Rank;

final readonly class HeroClass
{
    /** @param list<HeroSkill> $skills */
    public function __construct(
        public CharacterClass $class,
        public ClassPoints $points,
        public array $skills,
    ) {}

    public function rank(): Rank
    {
        return Rank::of($this->points);
    }
}
