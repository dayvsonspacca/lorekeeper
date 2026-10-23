<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

use Lorekeeper\Core\Support\Tag\Tag;

enum QuestTag implements Tag
{
    case MemberOnly;
    case Daily;
    case Weekly;
    case Monthly;
}
