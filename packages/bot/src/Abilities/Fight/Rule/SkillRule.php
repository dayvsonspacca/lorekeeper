<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Fight\Rule;

use Lorekeeper\Core\Game\Attribute\Vitals;

interface SkillRule
{
    public function allows(Vitals $vitals): bool;
}
