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
use Lorekeeper\Core\Item\Equipment\Traits\Enchantable;
use Lorekeeper\Core\Item\Equipment\Traits\GrantsBoosts;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class EquipmentTest extends TestCase
{
    #[Test]
    public function each_kind_of_equipment_goes_in_its_own_slot(): void
    {
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
            array_map(static fn(Equipment $equipment): EquipmentSlot => $equipment->slot(), self::kinds()),
        );
    }

    #[Test]
    public function only_classes_weapons_helms_and_capes_can_be_enchanted(): void
    {
        static::assertSame(
            [true, false, true, true, true, false, false, false],
            array_map(static fn(Equipment $equipment): bool => $equipment instanceof Enchantable, self::kinds()),
        );
    }

    #[Test]
    public function only_armors_weapons_helms_capes_and_pets_grant_boosts(): void
    {
        static::assertSame(
            [false, true, true, true, true, true, false, false],
            array_map(static fn(Equipment $equipment): bool => $equipment instanceof GrantsBoosts, self::kinds()),
        );
    }

    #[Test]
    public function an_enchantable_equipment_takes_the_enhancement_applied_to_it(): void
    {
        $lucky = new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none());
        $enchantables = array_values(array_filter(
            self::kinds(),
            static fn(Equipment $equipment): bool => $equipment instanceof Enchantable,
        ));

        foreach ($enchantables as $equipment) {
            static::assertInstanceOf(Enchantable::class, $equipment);
            static::assertTrue($equipment->enhancement->isNone());

            $equipment->enchant($lucky);

            static::assertSame($lucky, $equipment->enhancement->unwrap());
        }

        static::assertCount(4, $enchantables);
    }

    /** @return list<Equipment> */
    private static function kinds(): array
    {
        $id = new ItemId(1);
        $name = new ItemName('Hero Item');

        return [
            new ClassItem($id, $name, new ItemLevel(1), Option\none()),
            new Armor($id, $name, new ItemLevel(1)),
            new Weapon(
                $id,
                $name,
                new ItemLevel(1),
                WeaponType::Sword,
                new WeaponDamage(new DamageVariance(10)),
                Option\none(),
            ),
            new Helm($id, $name, new ItemLevel(1), Option\none()),
            new Cape($id, $name, new ItemLevel(1), Option\none()),
            new Pet($id, $name, new ItemLevel(1)),
            new GroundItem($id, $name, new ItemLevel(1)),
            new Necklace($id, $name, new ItemLevel(1)),
        ];
    }
}
