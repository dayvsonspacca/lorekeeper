<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Class;

use Lorekeeper\Core\Game\Rank\Rank;
use Lorekeeper\Core\Item\Equipment\CharacterClass\CharacterClass;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassPoints;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Passive\ClassPassive;
use Lorekeeper\Core\Item\Equipment\CharacterClass\Skill\Skill;

final class HeroClass
{
    /** @param list<HeroSkill> $skills */
    public function __construct(
        public private(set) CharacterClass $class,
        public private(set) ClassPoints $points,
        public private(set) array $skills = [],
    ) {}

    public function rank(): Rank
    {
        return Rank::of($this->points);
    }

    public function earn(ClassPoints $points): void
    {
        $this->points = new ClassPoints($this->points->value + $points->value);
    }

    public function change(CharacterClass $class, ClassPoints $points): void
    {
        $this->class = $class;
        $this->points = $points;
        $this->skills = [];
    }

    /**
     * @param list<HeroSkill> $skills
     * @param list<ClassPassive> $passives
     */
    public function learn(array $skills, array $passives): void
    {
        $this->class = new CharacterClass(
            $this->class->name,
            $this->class->category,
            $this->class->description,
            array_map(static fn(HeroSkill $held): Skill => $held->skill, $skills),
            $passives,
        );
        $this->skills = $skills;
    }
}
