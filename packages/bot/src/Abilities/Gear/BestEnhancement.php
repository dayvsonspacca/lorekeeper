<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Gear;

use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\World\Shop\CurrencyShopItem;

final readonly class BestEnhancement
{
    /** @param list<CurrencyShopItem> $offer */
    public static function for(PlayerItem $held, EnhancementType $type, array $offer, Player $player): ?CurrencyShopItem
    {
        $usable = UsableEnhancements::among($offer, $held, $player);
        usort($usable, static fn(CurrencyShopItem $a, CurrencyShopItem $b): int => self::rank($b) <=> self::rank($a));
        $best = $usable[0] ?? null;

        return $best !== null && self::improves($best, $held, $type) ? $best : null;
    }

    private static function rank(CurrencyShopItem $sold): int
    {
        $item = $sold->item;

        return $item instanceof EnhancementItem ? self::strength($item->enhancement) : 0;
    }

    private static function strength(Enhancement $enhancement): int
    {
        return (
            ($enhancement->level->value * 100)
            + (int) array_search($enhancement->rarity, EnhancementRarity::cases(), strict: true)
        );
    }

    private static function improves(CurrencyShopItem $best, PlayerItem $held, EnhancementType $type): bool
    {
        $offered = $best->item;
        $item = $held->item;
        if (!$offered instanceof EnhancementItem) {
            return false;
        }

        if (
            !$item instanceof Weapon
            && !$item instanceof ClassItem
            && !$item instanceof Helm
            && !$item instanceof Cape
        ) {
            return false;
        }

        $current = $item->enhancement->unwrapOr(null);
        if (
            $current?->type === EnhancementType::Unhandled
            || $offered->enhancement->type === EnhancementType::Unhandled
        ) {
            return false;
        }

        return (
            $current === null
            || $current->type !== $type
            || self::strength($current) < self::strength($offered->enhancement)
        );
    }
}
