<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass\Skill;

enum SkillTarget
{
    case Enemy;
    case Self;
    case Ally;
}
