<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Item\Helper;

use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Item\Helper\Enhancements;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class EnhancementsTest extends TestCase
{
    #[Test]
    #[TestWith([1, EnhancementType::Adventurer])]
    #[TestWith([2, EnhancementType::Fighter])]
    #[TestWith([3, EnhancementType::Thief])]
    #[TestWith([5, EnhancementType::Hybrid])]
    #[TestWith([6, EnhancementType::Wizard])]
    #[TestWith([7, EnhancementType::Healer])]
    #[TestWith([8, EnhancementType::SpellBreaker])]
    #[TestWith([9, EnhancementType::Lucky])]
    #[TestWith([10, EnhancementType::Unhandled])]
    public function it_reads_the_type_of_an_enhancement(int $type, EnhancementType $expected): void
    {
        static::assertSame($expected, Enhancements::type($type));
    }

    #[Test]
    #[TestWith([1, EnhancementRarity::PlusZero])]
    #[TestWith([2, EnhancementRarity::PlusOne])]
    #[TestWith([3, EnhancementRarity::PlusTwo])]
    #[TestWith([4, EnhancementRarity::PlusThree])]
    #[TestWith([5, EnhancementRarity::PlusFour])]
    #[TestWith([6, EnhancementRarity::PlusFive])]
    #[TestWith([7, EnhancementRarity::PlusSix])]
    #[TestWith([8, EnhancementRarity::PlusOneHundred])]
    #[TestWith([9, EnhancementRarity::PlusTwoHundred])]
    public function it_reads_the_rarity_of_an_enhancement(int $rarity, EnhancementRarity $expected): void
    {
        static::assertSame($expected, Enhancements::rarity($rarity, PlayerInventoryLoadedEvent::class));
    }

    #[Test]
    public function an_item_without_an_enhancement_has_no_type_or_rarity(): void
    {
        static::assertNull(Enhancements::type(0));
        static::assertNull(Enhancements::rarity(0, PlayerInventoryLoadedEvent::class));
    }

    #[Test]
    #[TestWith(['Weapon', EquipmentSlot::Weapon])]
    #[TestWith(['ar', EquipmentSlot::CharacterClass])]
    #[TestWith(['he', EquipmentSlot::Helm])]
    #[TestWith(['ba', EquipmentSlot::Cape])]
    public function it_reads_where_an_enhancement_sold_in_a_shop_fits(string $slot, EquipmentSlot $expected): void
    {
        static::assertSame($expected, self::sold(['PatternID' => 9, 'iRty' => 1, 'sES' => $slot])->slot);
    }

    #[Test]
    public function an_enhancement_sold_in_a_shop_without_a_type_cannot_be_read(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('enhancement 3628 has the unknown type 0 or rarity 1');

        self::sold(['PatternID' => 0, 'iRty' => 1, 'sES' => 'Weapon']);
    }

    #[Test]
    public function an_enhancement_sold_in_a_shop_for_an_unknown_slot_cannot_be_read(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("an enhancement fits the unknown slot 'co'");

        self::sold(['PatternID' => 9, 'iRty' => 1, 'sES' => 'co']);
    }

    #[Test]
    public function an_item_with_an_enhancement_it_cannot_read_is_rejected(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('an enhancement on an item is invalid');

        Enhancements::applied(
            ['EnhPatternID' => 9, 'EnhLvl' => 101, 'EnhRty' => 7],
            [],
            PlayerInventoryLoadedEvent::class,
        );
    }

    #[Test]
    public function an_item_with_an_effect_it_cannot_read_is_rejected(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('effect 4 has no name or description');

        Enhancements::applied(
            ['EnhPatternID' => 9, 'EnhLvl' => 100, 'EnhRty' => 7, 'ProcID' => 4],
            [4 => ['nam' => 'Health Vampire']],
            PlayerInventoryLoadedEvent::class,
        );
    }

    #[Test]
    public function a_cape_carries_its_enhancement(): void
    {
        $cape = ItemKinds::read([
            'ItemID' => 1,
            'sName' => 'Hero Item',
            'sType' => 'Cape',
            'iLvl' => 1,
            'EnhPatternID' => 2,
            'EnhLvl' => 50,
            'EnhRty' => 3,
        ], PlayerInventoryLoadedEvent::class);

        static::assertInstanceOf(Cape::class, $cape);
        static::assertSame(EnhancementType::Fighter, $cape->enhancement->unwrap()->type);
        static::assertSame(EnhancementRarity::PlusTwo, $cape->enhancement->unwrap()->rarity);
    }

    /** @param array<string, mixed> $entry */
    private static function sold(array $entry): EnhancementItem
    {
        return Enhancements::sold(
            new ItemId(3628),
            new ItemName('Lucky Weapon Enhancement'),
            new ItemLevel(21),
            $entry,
            ShopLoadedEvent::class,
        );
    }
}
