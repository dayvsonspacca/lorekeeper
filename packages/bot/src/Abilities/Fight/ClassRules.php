<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Fight;

use Lorekeeper\Bot\Abilities\Fight\Rule\HealthBelow;
use Lorekeeper\Bot\Abilities\Fight\Rule\ManaAbove;
use Lorekeeper\Bot\Abilities\Fight\Rule\SkillRule;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassName;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Skill;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\SkillSlot;

final readonly class ClassRules
{
    /** @param array<string, non-empty-list<SkillRule>> $slots */
    private function __construct(
        private array $slots = [],
    ) {}

    public static function of(ClassName $class): self
    {
        return match (strtolower($class->value)) {
            'oracle' => new self([
                SkillSlot::Second->name => [new HealthBelow(50), new ManaAbove(50)],
            ]),
            default => new self(),
        };
    }

    public static function none(): self
    {
        return new self();
    }

    public function allows(Skill $skill, Vitals $vitals): bool
    {
        foreach ($this->slots[$skill->slot->name] ?? [] as $rule) {
            if (!$rule->allows($vitals)) {
                return false;
            }
        }

        return true;
    }

    public function guards(Skill $skill): bool
    {
        return array_key_exists($skill->slot->name, $this->slots);
    }
}
