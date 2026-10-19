<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill\Cost;

use Lorekeeper\Core\Attribute\Vitals;

interface SkillCost
{
    public function affordable(Vitals $vitals): bool;
}
