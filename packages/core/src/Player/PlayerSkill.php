<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\CharacterClass\Skill\Skill;

final readonly class PlayerSkill
{
    public function __construct(
        public Skill $skill,
        public bool $unlocked,
    ) {}
}
