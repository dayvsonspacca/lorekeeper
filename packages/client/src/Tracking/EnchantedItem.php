<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Psl\Option;

final readonly class EnchantedItem
{
    public static function from(Item $item, Enhancement $enhancement): Item
    {
        return self::tagged($item, self::enchanted($item, Option\some($enhancement)));
    }

    /**
     * @param Option\Option<Enhancement> $with
     *
     * @mago-expect lint:halstead
     */
    private static function enchanted(Item $item, Option\Option $with): Item
    {
        return match (true) {
            $item instanceof Weapon => new Weapon(
                $item->id,
                $item->name,
                $item->level,
                $item->type,
                $item->damage,
                $with,
            ),
            $item instanceof ClassItem => new ClassItem($item->id, $item->name, $item->level, $with),
            $item instanceof Helm => new Helm($item->id, $item->name, $item->level, $with),
            $item instanceof Cape => new Cape($item->id, $item->name, $item->level, $with),
            default => $item,
        };
    }

    private static function tagged(Item $was, Item $item): Item
    {
        foreach (ItemTag::cases() as $tag) {
            if (!$was->tags->has($tag)) {
                continue;
            }

            $item->tags->add($tag);
        }

        return $item;
    }
}
