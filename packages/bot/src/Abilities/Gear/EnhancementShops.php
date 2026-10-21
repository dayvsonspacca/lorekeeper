<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Abilities\Gear;

use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\World\Shop\ShopId;

final readonly class EnhancementShops
{
    private const int HIGHER_SHOPS_FROM_LEVEL = 50;

    public static function selling(EnhancementType $type, PlayerLevel $level): ?ShopId
    {
        $shops = match ($type) {
            EnhancementType::Fighter => [141, 768],
            EnhancementType::Thief => [142, 767],
            EnhancementType::Hybrid => [143, 766],
            EnhancementType::Wizard => [144, 765],
            EnhancementType::Healer => [145, 762],
            EnhancementType::SpellBreaker => [146, 764],
            EnhancementType::Lucky => [147, 763],
            EnhancementType::Adventurer, EnhancementType::Unhandled => null,
        };
        if ($shops === null) {
            return null;
        }

        return new ShopId($level->value >= self::HIGHER_SHOPS_FROM_LEVEL ? $shops[1] : $shops[0]);
    }
}
