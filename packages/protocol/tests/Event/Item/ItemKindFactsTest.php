<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Item;

use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Protocol\Event\Item\ItemKindFacts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ItemKindFactsTest extends TestCase
{
    #[Test]
    #[TestWith(['Weapon', EquipmentSlot::Weapon])]
    #[TestWith(['ar', EquipmentSlot::Armor])]
    #[TestWith(['co', EquipmentSlot::Costume])]
    #[TestWith(['he', EquipmentSlot::Helm])]
    #[TestWith(['ba', EquipmentSlot::Cape])]
    #[TestWith(['pe', EquipmentSlot::Pet])]
    #[TestWith(['am', EquipmentSlot::Accessory])]
    #[TestWith(['ho', EquipmentSlot::House])]
    #[TestWith(['mi', EquipmentSlot::Ground])]
    public function it_translates_slot_codes(string $code, EquipmentSlot $slot): void
    {
        static::assertSame($slot, ItemKindFacts::of(new ItemId(1), '', $code)[0]->value);
    }

    #[Test]
    public function it_skips_unknown_slots_and_blank_types(): void
    {
        static::assertSame([], ItemKindFacts::of(new ItemId(1), ' ', 'None'));
    }
}
