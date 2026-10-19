<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

enum MonsterRace
{
    case None;
    case Undead;
    case Human;
    case Elemental;
    case Dragonkin;
    case Unknown;
}
