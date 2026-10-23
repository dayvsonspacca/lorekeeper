<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Fight;

use Lorekeeper\Core\Game\Attribute\Stat\Haste;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final readonly class GlobalCooldown
{
    private const int SKILLS_MS = 1500;

    private const int SPACING_MS = 200;

    private const int REFUSED_DELAY_MS = 250;

    public function __construct(
        private ?Timestamp $skills = null,
        private ?Timestamp $actions = null,
        public ?SkillSlot $last = null,
    ) {}

    public function holds(Skill $skill, Timestamp $now): bool
    {
        return self::pending($this->actions, $now) || self::shares($skill) && self::pending($this->skills, $now);
    }

    public function used(Skill $skill, Timestamp $now, Haste $haste): self
    {
        $actions = $now->plus(Duration::milliseconds(self::SPACING_MS));
        $skills = (int) round(self::SKILLS_MS * (1 - $haste->effective()));

        return self::shares($skill)
            ? new self($now->plus(Duration::milliseconds($skills)), $actions, $skill->slot)
            : new self($this->skills, $actions, $this->last);
    }

    public function refused(Timestamp $now): self
    {
        return new self($now->plus(Duration::milliseconds(self::REFUSED_DELAY_MS)), $this->actions);
    }

    /** @return list<Timestamp> */
    public function ends(): array
    {
        return array_values(array_filter([$this->skills, $this->actions]));
    }

    private static function shares(Skill $skill): bool
    {
        return $skill->slot !== SkillSlot::AutoAttack;
    }

    private static function pending(?Timestamp $until, Timestamp $now): bool
    {
        return $until !== null && $until->after($now);
    }
}
