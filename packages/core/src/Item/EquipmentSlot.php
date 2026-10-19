<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

enum EquipmentSlot: string
{
    case CharacterClass = 'CharacterClass';
    case Weapon = 'Weapon';
    case Armor = 'Armor';
    case Helm = 'Helm';
    case Cape = 'Cape';
    case Pet = 'Pet';
    case Ground = 'Ground';
    case Necklace = 'Necklace';
}
