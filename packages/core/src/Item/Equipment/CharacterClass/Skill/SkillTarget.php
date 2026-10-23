<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Equipment\CharacterClass\Skill;

enum SkillTarget
{
    case Enemy;
    case Self;
    case Ally;
}
