<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Type;

enum Misc
{
    case Boost;
    case Enhancement;
    case Generic;

    public function parent(): ItemCategory
    {
        return ItemCategory::Misc;
    }
}
