<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Type;

enum House
{
    case Floor;
    case House;
    case Wall;

    public function parent(): Category
    {
        return Category::House;
    }
}
