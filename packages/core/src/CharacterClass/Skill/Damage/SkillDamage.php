<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill\Damage;

final readonly class SkillDamage
{
    public function __construct(
        public DamageMultiplier $multiplier,
        public DamageSource $source,
    ) {}
}
