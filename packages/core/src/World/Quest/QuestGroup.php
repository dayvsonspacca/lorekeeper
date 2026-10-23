<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

enum QuestGroup
{
    case Daily;
    case Weekly;
    case Monthly;
}
