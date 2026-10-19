<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\CharacterClass\CharacterClass;
use Lorekeeper\Core\Progression\ClassPoints;

final readonly class PlayerClass
{
    public function __construct(
        public CharacterClass $class,
        public ClassPoints $points,
    ) {}
}
