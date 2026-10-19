<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item\Type;

use Lorekeeper\Core\Item\Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TypeTreeTest extends TestCase
{
    #[Test]
    public function every_weapon_is_an_equipment_weapon(): void
    {
        foreach (Type\Weapon::cases() as $weapon) {
            static::assertSame(Type\Equipment::Weapon, $weapon->parent());
        }
    }

    #[Test]
    public function every_equipment_belongs_to_the_equipment_category(): void
    {
        foreach (Type\Equipment::cases() as $equipment) {
            static::assertSame(Type\ItemCategory::Equipment, $equipment->parent());
        }
    }

    #[Test]
    public function every_house_item_belongs_to_the_house_category(): void
    {
        foreach (Type\House::cases() as $house) {
            static::assertSame(Type\ItemCategory::House, $house->parent());
        }
    }

    #[Test]
    public function every_misc_item_belongs_to_the_misc_category(): void
    {
        foreach (Type\Misc::cases() as $misc) {
            static::assertSame(Type\ItemCategory::Misc, $misc->parent());
        }
    }
}
