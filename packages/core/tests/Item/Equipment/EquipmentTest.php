<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item\Equipment;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Equipment;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon;
use Lorekeeper\Core\Item\Equipment\WeaponType;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EquipmentTest extends TestCase
{
    #[Test]
    public function each_kind_of_equipment_goes_in_its_own_slot(): void
    {
        $id = new ItemId(1);
        $name = new ItemName('Hero Item');

        static::assertSame(
            [
                EquipmentSlot::CharacterClass,
                EquipmentSlot::Armor,
                EquipmentSlot::Weapon,
                EquipmentSlot::Helm,
                EquipmentSlot::Cape,
                EquipmentSlot::Pet,
                EquipmentSlot::Ground,
                EquipmentSlot::Necklace,
            ],
            array_map(static fn(Equipment $equipment): EquipmentSlot => $equipment->slot(), [
                new ClassItem($id, $name),
                new Armor($id, $name),
                new Weapon($id, $name, WeaponType::Sword),
                new Helm($id, $name),
                new Cape($id, $name),
                new Pet($id, $name),
                new GroundItem($id, $name),
                new Necklace($id, $name),
            ]),
        );
    }
}
