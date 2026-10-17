<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill;

use Lorekeeper\Core\Attribute\Mana;
use Psl\DateTime\Duration;

final readonly class Skill
{
    public function __construct(
        public SkillSlot $slot,
        public SkillTarget $target,
        public Mana $mana,
        public Duration $cooldown,
    ) {}
}
