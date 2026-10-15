<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Item;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;

final readonly class ItemAssetFacts
{
    /** @return list<Fact<ItemId, Gamefile|Linkage>> */
    public static function of(ItemId $itemId, string $file, string $link): array
    {
        if (!str_ends_with($file, '.swf')) {
            return [];
        }

        return (
            $link === ''
                ? [new Fact($itemId, new Gamefile($file))]
                : [new Fact($itemId, new Gamefile($file)), new Fact($itemId, new Linkage($link))]
        );
    }
}
