<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\CharacterClass\Skill\Skill;

final readonly class HeroSkill
{
    public function __construct(
        public Skill $skill,
        public bool $unlocked,
    ) {}
}
