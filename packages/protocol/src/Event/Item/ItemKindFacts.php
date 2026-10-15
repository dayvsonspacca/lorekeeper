<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Item;

use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemType;
use Lorekeeper\Core\Knowledge\Fact;

final readonly class ItemKindFacts
{
    /** @return list<Fact<ItemId, ItemType|EquipmentSlot>> */
    public static function of(ItemId $itemId, string $type, string $slot): array
    {
        $facts = trim($type) === '' ? [] : [new Fact($itemId, new ItemType($type))];
        $equipmentSlot = self::slot($slot);

        return $equipmentSlot === null ? $facts : [...$facts, new Fact($itemId, $equipmentSlot)];
    }

    private static function slot(string $code): ?EquipmentSlot
    {
        return match ($code) {
            'Weapon' => EquipmentSlot::Weapon,
            'ar', 'co' => EquipmentSlot::Armor,
            'he' => EquipmentSlot::Helm,
            'ba' => EquipmentSlot::Cape,
            'pe' => EquipmentSlot::Pet,
            'mi' => EquipmentSlot::Ground,
            default => null,
        };
    }
}
