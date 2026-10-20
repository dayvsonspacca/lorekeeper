<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill;

use Lorekeeper\Core\CharacterClass\Skill\Cost\SkillCost;
use Lorekeeper\Core\CharacterClass\Skill\Damage\SkillDamage;
use Lorekeeper\Core\Support\Quantity;
use Psl\DateTime\Duration;
use Psl\Option;

final readonly class Skill
{
    /**
     * @param Option\Option<SkillDescription> $description
     * @param Option\Option<SkillDamage> $damage
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public SkillSlot $slot,
        public SkillName $name,
        public Option\Option $description,
        public SkillTarget $target,
        public SkillCost $cost,
        public Duration $cooldown,
        public Quantity $targets,
        public Option\Option $damage,
    ) {}
}
