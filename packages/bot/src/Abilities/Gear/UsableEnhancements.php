<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Gear;

use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Shop\CurrencyShop;

final readonly class UsableEnhancements
{
    /** @return list<EnhancementItem> */
    public static function among(CurrencyShop $shop, Equipment $held, Hero $hero): array
    {
        $usable = [];
        foreach ($shop->inventory as $sold) {
            if (!self::usable($sold, $shop, $held, $hero)) {
                continue;
            }

            $usable[] = $sold;
        }

        return $usable;
    }

    /** @phpstan-assert-if-true EnhancementItem $sold */
    private static function usable(Item $sold, CurrencyShop $shop, Equipment $held, Hero $hero): bool
    {
        return (
            $sold instanceof EnhancementItem
            && $sold->fits($held)
            && $sold->level->value <= $hero->level->value
            && ($hero->tags->has(PlayerTag::Member) || !$sold->tags->has(ItemTag::MemberOnly))
            && $shop->price($sold->id)->isSomeAnd(
                static fn(ItemPrice $price): bool => (
                    $price->amount instanceof Gold
                    && $hero->inventory->wallet->affords($price)
                ),
            )
        );
    }
}
