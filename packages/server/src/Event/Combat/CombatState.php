<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

enum CombatState: int
{
    case Dead = 0;
    case Idle = 1;
    case Fighting = 2;
}
