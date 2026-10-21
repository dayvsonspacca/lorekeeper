<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Misc\Enhancement;

enum EnhancementType
{
    case Adventurer;
    case Fighter;
    case Thief;
    case Hybrid;
    case Wizard;
    case Healer;
    case SpellBreaker;
    case Lucky;
    case Unhandled;
}
