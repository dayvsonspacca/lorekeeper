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
    #[TestWith(['co', EquipmentSlot::Armor])]
    #[TestWith(['he', EquipmentSlot::Helm])]
    #[TestWith(['ba', EquipmentSlot::Cape])]
    #[TestWith(['pe', EquipmentSlot::Pet])]
    #[TestWith(['mi', EquipmentSlot::Ground])]
    public function it_translates_slot_codes(string $code, EquipmentSlot $slot): void
    {
        static::assertSame($slot, ItemKindFacts::of(new ItemId(1), '', $code)[0]->value);
    }

    #[Test]
    #[TestWith(['am'])]
    #[TestWith(['ho'])]
    #[TestWith(['None'])]
    public function it_has_no_slot_for_necklaces_houses_and_unknown_codes(string $code): void
    {
        static::assertSame([], ItemKindFacts::of(new ItemId(1), '', $code));
    }

    #[Test]
    public function it_skips_unknown_slots_and_blank_types(): void
    {
        static::assertSame([], ItemKindFacts::of(new ItemId(1), ' ', 'None'));
    }
}
