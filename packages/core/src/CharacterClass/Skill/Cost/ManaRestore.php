<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill\Cost;

use Lorekeeper\Core\Game\Attribute\Mana;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Override;

final readonly class ManaRestore implements SkillCost
{
    public function __construct(
        public Mana $mana,
    ) {}

    #[Override]
    public function affordable(Vitals $vitals): bool
    {
        return true;
    }
}
