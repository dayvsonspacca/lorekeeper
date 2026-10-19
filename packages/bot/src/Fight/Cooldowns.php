<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Fight;

use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final readonly class Cooldowns
{
    private const string ANY = 'any';

    /** @param array<string, Timestamp> $ready */
    public function __construct(
        private array $ready = [],
    ) {}

    /** @param list<Skill> $skills */
    public function ready(array $skills, Mana $mana, Timestamp $now): ?Skill
    {
        if ($this->waiting(self::ANY, $now)) {
            return null;
        }

        $usable = array_values(array_filter(
            $skills,
            fn(Skill $skill): bool => (
                $skill->target === SkillTarget::Enemy
                && !$this->waiting($skill->slot->name, $now)
                && $skill->cost->value <= $mana->value
            ),
        ));

        usort(
            $usable,
            static fn(Skill $a, Skill $b): int => (
                (int) ($a->slot === SkillSlot::AutoAttack) <=> (int) ($b->slot === SkillSlot::AutoAttack)
            ),
        );

        return $usable[0] ?? null;
    }

    public function used(Skill $skill, Timestamp $now): self
    {
        return new self([
            ...$this->ready,
            $skill->slot->name => $now->plus($skill->cooldown),
            self::ANY => $now->plus(Duration::seconds(1)),
        ]);
    }

    public function wait(Timestamp $now): Duration
    {
        $soonest = Duration::seconds(2);
        foreach ($this->ready as $at) {
            $left = $at->since($now);
            if ($left->isPositive() && $left->compare($soonest) < 0) {
                $soonest = $left;
            }
        }

        return $soonest->compare(Duration::milliseconds(200)) < 0 ? Duration::milliseconds(200) : $soonest;
    }

    private function waiting(string $slot, Timestamp $now): bool
    {
        return array_key_exists($slot, $this->ready) && $this->ready[$slot]->after($now);
    }
}
