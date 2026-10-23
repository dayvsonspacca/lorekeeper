<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Fight\Rule;

use Lorekeeper\Core\Game\Attribute\Vitals;
use Override;

final readonly class ManaAbove implements SkillRule
{
    public function __construct(
        private int $percent,
    ) {}

    #[Override]
    public function allows(Vitals $vitals): bool
    {
        $mana = $vitals->mana;

        return ($mana->current->value * 100) > ($mana->maximum->value * $this->percent);
    }
}
