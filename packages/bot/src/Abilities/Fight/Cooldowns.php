<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Fight;

use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Psl\Comparison\Order;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;

final readonly class Cooldowns
{
    /** @param array<string, Timestamp> $ready */
    public function __construct(
        private array $ready = [],
        private GlobalCooldown $global = new GlobalCooldown(),
    ) {}

    /** @param list<Skill> $skills */
    public function ready(array $skills, Vitals $vitals, ClassRules $rules, Timestamp $now): ?Skill
    {
        $usable = array_values(array_filter(
            $skills,
            fn(Skill $skill): bool => (
                $skill->slot !== SkillSlot::Potion
                && !$this->waiting($skill->slot->name, $now)
                && !$this->global->holds($skill, $now)
                && $skill->cost->affordable($vitals)
                && $rules->allows($skill, $vitals)
            ),
        ));

        usort($usable, static fn(Skill $a, Skill $b): int => self::turn($a, $rules) <=> self::turn($b, $rules));

        return $usable[0] ?? null;
    }

    public function used(Skill $skill, Timestamp $now, Haste $haste): self
    {
        $cooldown = (int) round($skill->cooldown->getTotalMilliseconds() * (1 - $haste->effective()));

        return new self(
            [...$this->ready, $skill->slot->name => $now->plus(Duration::milliseconds($cooldown))],
            $this->global->used($skill, $now, $haste),
        );
    }

    public function refused(Timestamp $now): self
    {
        $ready = $this->ready;
        unset($ready[$this->global->last->name ?? '']);

        return new self($ready, $this->global->refused($now));
    }

    public function wait(Timestamp $now): Duration
    {
        $soonest = Duration::seconds(2);
        foreach ([...array_values($this->ready), ...$this->global->ends()] as $at) {
            $left = $at->since($now);
            if ($left->isPositive() && $left->compare($soonest) === Order::Less) {
                $soonest = $left;
            }
        }

        return $soonest->compare(Duration::milliseconds(200)) === Order::Less ? Duration::milliseconds(200) : $soonest;
    }

    private static function turn(Skill $skill, ClassRules $rules): int
    {
        return match (true) {
            $rules->guards($skill) => 0,
            $skill->slot === SkillSlot::AutoAttack => 2,
            default => 1,
        };
    }

    private function waiting(string $slot, Timestamp $now): bool
    {
        return array_key_exists($slot, $this->ready) && $this->ready[$slot]->after($now);
    }
}
