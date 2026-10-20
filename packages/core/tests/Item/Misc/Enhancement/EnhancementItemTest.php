<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item\Misc\Enhancement;

use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class EnhancementItemTest extends TestCase
{
    #[Test]
    public function it_is_as_high_as_the_enhancement_it_applies_and_fits_only_its_slot(): void
    {
        $lucky = new EnhancementItem(
            new ItemId(3628),
            new ItemName('Lucky Weapon Enhancement'),
            new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none()),
            EquipmentSlot::Weapon,
        );
        $staff = new Weapon(
            new ItemId(3),
            new ItemName('Default Staff'),
            new ItemLevel(1),
            WeaponType::Staff,
            new WeaponDamage(new DamageVariance(10)),
            Option\none(),
        );
        $cape = new Cape(new ItemId(29_375), new ItemName('Cape of Awe'), new ItemLevel(1), Option\none());

        static::assertSame(21, $lucky->level->value);
        static::assertTrue($lucky->fits($staff));
        static::assertFalse($lucky->fits($cape));
    }
}
