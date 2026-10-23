<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Game\Tag\Tag;

enum ItemTag implements Tag
{
    case MemberOnly;
    case Temporary;
}
