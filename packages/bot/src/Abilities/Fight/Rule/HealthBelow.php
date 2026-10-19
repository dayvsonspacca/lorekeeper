<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Fight\Rule;

use Lorekeeper\Core\Attribute\Vitals;
use Override;

final readonly class HealthBelow implements SkillRule
{
    public function __construct(
        private int $percent,
    ) {}

    #[Override]
    public function allows(Vitals $vitals): bool
    {
        $health = $vitals->health;

        return ($health->current->value * 100) < ($health->maximum->value * $this->percent);
    }
}
