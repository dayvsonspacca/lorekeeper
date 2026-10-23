<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Game\Tag\Tag;

enum ShopTag implements Tag
{
    case MemberOnly;
    case House;
}
