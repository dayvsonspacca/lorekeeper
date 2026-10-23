<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Gear;

use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\Equipment\Traits\Enchantable;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\Hero\Hero;
use Lorekeeper\Core\World\Shop\CurrencyShop;

final readonly class BestEnhancement
{
    public static function for(
        Equipment&Enchantable $held,
        EnhancementType $type,
        CurrencyShop $shop,
        Hero $hero,
    ): ?EnhancementItem {
        $usable = UsableEnhancements::among($shop, $held, $hero);
        usort(
            $usable,
            static fn(EnhancementItem $a, EnhancementItem $b): int => (
                self::strength($b->enhancement) <=> self::strength($a->enhancement)
            ),
        );
        $best = $usable[0] ?? null;

        return $best !== null && self::improves($best, $held, $type) ? $best : null;
    }

    private static function strength(Enhancement $enhancement): int
    {
        return (
            ($enhancement->level->value * 100)
            + (int) array_search($enhancement->rarity, EnhancementRarity::cases(), strict: true)
        );
    }

    private static function improves(EnhancementItem $best, Enchantable $held, EnhancementType $type): bool
    {
        $current = $held->enhancement->unwrapOr(null);
        if ($current?->type === EnhancementType::Unhandled || $best->enhancement->type === EnhancementType::Unhandled) {
            return false;
        }

        return (
            $current === null
            || $current->type !== $type
            || self::strength($current) < self::strength($best->enhancement)
        );
    }
}
