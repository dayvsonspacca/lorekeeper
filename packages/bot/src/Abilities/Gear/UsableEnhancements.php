<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Gear;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\World\Shop\CurrencyShopItem;

final readonly class UsableEnhancements
{
    /**
     * @param list<CurrencyShopItem> $offer
     *
     * @return list<CurrencyShopItem>
     */
    public static function among(array $offer, PlayerItem $held, Player $player): array
    {
        return array_values(array_filter($offer, static fn(CurrencyShopItem $sold): bool => self::usable(
            $sold,
            $held,
            $player,
        )));
    }

    private static function usable(CurrencyShopItem $sold, PlayerItem $held, Player $player): bool
    {
        $enhancement = $sold->item;
        $price = $sold->price->amount;

        return (
            $enhancement instanceof EnhancementItem
            && $enhancement->fits($held->item)
            && $enhancement->level->value <= $player->progress->level->value
            && ($player->tags->has(PlayerTag::Member) || !$enhancement->tags->has(ItemTag::MemberOnly))
            && $price instanceof Gold
            && $price->value <= $player->wallet->gold->value
        );
    }
}
