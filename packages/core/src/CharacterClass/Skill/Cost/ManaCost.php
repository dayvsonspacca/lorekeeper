<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill\Cost;

use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Override;

final readonly class ManaCost implements SkillCost
{
    public function __construct(
        public Mana $mana,
    ) {}

    #[Override]
    public function affordable(Vitals $vitals): bool
    {
        return $vitals->mana->current->value >= $this->mana->value;
    }
}
