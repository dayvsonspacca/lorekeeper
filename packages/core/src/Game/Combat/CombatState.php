<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Combat;

enum CombatState
{
    case Dead;
    case Idle;
    case Fighting;
}
