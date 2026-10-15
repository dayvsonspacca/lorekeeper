<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

enum EquipmentSlot
{
    case Weapon;
    case Armor;
    case Costume;
    case Helm;
    case Cape;
    case Pet;
    case Accessory;
    case House;
    case Ground;
}
