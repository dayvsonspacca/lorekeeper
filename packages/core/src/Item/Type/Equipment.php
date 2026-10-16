<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Type;

enum Equipment
{
    case Armor;
    case Cape;
    case CharacterClass;
    case Ground;
    case Helm;
    case Necklace;
    case Pet;
    case Weapon;

    public function parent(): Category
    {
        return Category::Equipment;
    }
}
