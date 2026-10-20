<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass;

use Lorekeeper\Core\CharacterClass\Passive\ClassPassive;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Psl\Option\Option;

final readonly class CharacterClass
{
    /**
     * @param Option<ClassDescription> $description
     * @param list<Skill> $skills
     * @param list<ClassPassive> $passives
     */
    public function __construct(
        public ClassName $name,
        public ClassCategory $category,
        public Option $description,
        public array $skills,
        public array $passives,
    ) {}
}
