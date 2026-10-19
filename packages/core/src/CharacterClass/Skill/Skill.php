<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill;

use Lorekeeper\Core\CharacterClass\Skill\Cost\SkillCost;
use Lorekeeper\Core\Support\Quantity;
use Psl\DateTime\Duration;

final readonly class Skill
{
    public function __construct(
        public SkillSlot $slot,
        public SkillTarget $target,
        public SkillCost $cost,
        public Duration $cooldown,
        public Quantity $targets = new Quantity(1),
    ) {}
}
