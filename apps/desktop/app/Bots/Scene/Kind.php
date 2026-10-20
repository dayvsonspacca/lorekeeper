<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;

final readonly class Kind
{
    public static function of(Item $item): string
    {
        return match (true) {
            $item instanceof ClassItem => 'class',
            $item instanceof Armor => 'armor',
            $item instanceof Helm => 'helm',
            $item instanceof Cape => 'cape',
            $item instanceof Weapon => 'weapon',
            $item instanceof Pet => 'pet',
            $item instanceof Necklace => 'necklace',
            $item instanceof GroundItem => 'ground',
            $item instanceof HouseItem => 'house',
            $item instanceof Boost => 'boost',
            $item instanceof EnhancementItem => 'enhancement',
            default => 'resource',
        };
    }

    public static function label(Item $item): string
    {
        return match (true) {
            $item instanceof Weapon => $item->type->name,
            $item instanceof HouseItem => "{$item->type->name} item",
            default => ucfirst(self::of($item)),
        };
    }
}
