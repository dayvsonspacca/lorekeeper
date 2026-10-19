<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Support\Tag\Tag;

enum ItemTag implements Tag
{
    case MemberOnly;
}
