<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Item\Helper;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\House\HouseItemType;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Enhancement\EffectDescription;
use Lorekeeper\Core\Item\Misc\Enhancement\EffectName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementEffect;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psl\Option;

/** @mago-expect lint:too-many-methods */
final class ItemKindsTest extends TestCase
{
    /** @param class-string<Item> $kind */
    #[Test]
    #[TestWith(['Class', ClassItem::class])]
    #[TestWith(['Armor', Armor::class])]
    #[TestWith(['Helm', Helm::class])]
    #[TestWith(['Cape', Cape::class])]
    #[TestWith(['Pet', Pet::class])]
    #[TestWith(['Necklace', Necklace::class])]
    #[TestWith(['Misc', GroundItem::class])]
    #[TestWith(['ServerUse', Boost::class])]
    #[TestWith(['Item', Resource::class])]
    #[TestWith(['Note', Resource::class])]
    #[TestWith(['Quest Item', Resource::class])]
    #[TestWith(['Resource', Resource::class])]
    public function it_reads_the_kind_of_item(string $type, string $kind): void
    {
        static::assertInstanceOf($kind, self::read($type));
    }

    #[Test]
    #[TestWith(['Axe', WeaponType::Axe])]
    #[TestWith(['Bow', WeaponType::Bow])]
    #[TestWith(['Dagger', WeaponType::Dagger])]
    #[TestWith(['Gauntlet', WeaponType::Gauntlet])]
    #[TestWith(['Gun', WeaponType::Gun])]
    #[TestWith(['HandGun', WeaponType::HandGun])]
    #[TestWith(['Mace', WeaponType::Mace])]
    #[TestWith(['Polearm', WeaponType::Polearm])]
    #[TestWith(['Rifle', WeaponType::Rifle])]
    #[TestWith(['Staff', WeaponType::Staff])]
    #[TestWith(['Sword', WeaponType::Sword])]
    #[TestWith(['Wand', WeaponType::Wand])]
    #[TestWith(['Whip', WeaponType::Whip])]
    public function it_reads_the_type_of_weapon(string $type, WeaponType $weapon): void
    {
        $item = self::read($type);

        static::assertInstanceOf(Weapon::class, $item);
        static::assertSame($weapon, $item->type);
    }

    #[Test]
    #[TestWith(['Floor Item', HouseItemType::Floor])]
    #[TestWith(['Wall Item', HouseItemType::Wall])]
    #[TestWith(['House', HouseItemType::House])]
    public function it_reads_the_type_of_house_item(string $type, HouseItemType $house): void
    {
        $item = self::read($type);

        static::assertInstanceOf(HouseItem::class, $item);
        static::assertSame($house, $item->type);
    }

    #[Test]
    public function it_reads_what_an_enhancement_sold_in_a_shop_applies_and_where_it_fits(): void
    {
        $item = ItemKinds::read([
            'ItemID' => 3628,
            'sName' => 'Lucky Weapon Enhancement',
            'sType' => 'Enhancement',
            'sES' => 'Weapon',
            'iLvl' => 21,
            'iRty' => 1,
            'iRng' => 10,
            'PatternID' => 9,
        ], ShopLoadedEvent::class);

        static::assertInstanceOf(EnhancementItem::class, $item);
        static::assertEquals(
            new Enhancement(EnhancementType::Lucky, EnhancementRarity::PlusZero, new ItemLevel(21), Option\none()),
            $item->enhancement,
        );
        static::assertSame(EquipmentSlot::Weapon, $item->slot);
        static::assertSame(21, $item->level->value);
    }

    #[Test]
    public function a_weapon_carries_how_much_its_damage_varies_and_its_enhancement_with_the_effect(): void
    {
        $item = ItemKinds::read(
            [
                'ItemID' => 30_629,
                'sName' => 'Necrotic Sword of Doom',
                'sType' => 'Sword',
                'sES' => 'Weapon',
                'iLvl' => 1,
                'iRty' => 36,
                'iRng' => 10,
                'EnhLvl' => 100,
                'EnhRty' => 7,
                'EnhPatternID' => 9,
                'ProcID' => 4,
            ],
            InventoryLoadedEvent::class,
            [4 => [
                'nam' => 'Health Vampire',
                'desc' => 'Deals 80% damage and lifesteals for 125% base damage (before stats).',
                'typ' => 'ma',
                'cd' => 0,
                'range' => 3000,
                'damage' => 0.8,
                'tgt' => 'h',
                'pct' => 50,
                'ref' => 'aa',
            ]],
        );

        static::assertInstanceOf(Weapon::class, $item);
        static::assertSame(10, $item->damage->variance->percent);
        static::assertEquals(
            new Enhancement(
                EnhancementType::Lucky,
                EnhancementRarity::PlusSix,
                new ItemLevel(100),
                Option\some(
                    new EnhancementEffect(
                        new EffectName('Health Vampire'),
                        new EffectDescription('Deals 80% damage and lifesteals for 125% base damage (before stats).'),
                    ),
                ),
            ),
            $item->enhancement->unwrap(),
        );
    }

    #[Test]
    public function an_enhancement_of_a_type_it_does_not_know_is_read_as_unhandled(): void
    {
        $item = ItemKinds::read([
            'ItemID' => 37_207,
            'sName' => 'Dual Blinding Light of Destiny',
            'sType' => 'Dagger',
            'sES' => 'Weapon',
            'iLvl' => 1,
            'iRty' => 35,
            'iRng' => 0,
            'EnhLvl' => 80,
            'EnhRty' => 5,
            'EnhPatternID' => 10,
            'ProcID' => 7,
        ], InventoryLoadedEvent::class);

        static::assertInstanceOf(Weapon::class, $item);
        static::assertSame(0, $item->damage->variance->percent);
        static::assertSame(EnhancementType::Unhandled, $item->enhancement->unwrap()->type);
        static::assertSame(80, $item->enhancement->unwrap()->level->value);
    }

    #[Test]
    public function an_enhancement_of_a_type_it_does_not_know_can_still_be_sold(): void
    {
        $item = ItemKinds::read([
            'ItemID' => 3628,
            'sName' => 'Lucky Weapon Enhancement',
            'sType' => 'Enhancement',
            'sES' => 'Weapon',
            'iLvl' => 21,
            'iRty' => 1,
            'iRng' => 10,
            'PatternID' => 10,
        ], ShopLoadedEvent::class);

        static::assertInstanceOf(EnhancementItem::class, $item);
        static::assertSame(EnhancementType::Unhandled, $item->enhancement->type);
    }

    #[Test]
    public function an_enhancement_of_a_rarity_it_does_not_know_cannot_be_read(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('an enhancement has the unknown rarity 10');

        ItemKinds::read([
            'ItemID' => 37_207,
            'sName' => 'Dual Blinding Light of Destiny',
            'sType' => 'Dagger',
            'sES' => 'Weapon',
            'iLvl' => 1,
            'iRty' => 35,
            'iRng' => 0,
            'EnhLvl' => 80,
            'EnhRty' => 10,
            'EnhPatternID' => 10,
            'ProcID' => 7,
        ], InventoryLoadedEvent::class);
    }

    #[Test]
    public function an_enhancement_without_its_type_rarity_or_slot_cannot_be_read(): void
    {
        $this->expectException(MalformedEventException::class);

        self::read('Enhancement');
    }

    #[Test]
    public function a_resource_or_boost_stacks_as_high_as_the_item_says(): void
    {
        $scales = ItemKinds::read([
            'ItemID' => 11_475,
            'sName' => 'Dragon Scale',
            'sType' => 'Item',
            'iLvl' => 1,
            'iStk' => 1000,
        ], InventoryLoadedEvent::class);
        $boost = ItemKinds::read([
            'ItemID' => 19_189,
            'sName' => 'Experience Boost',
            'sType' => 'ServerUse',
            'iLvl' => 1,
        ], InventoryLoadedEvent::class);

        static::assertInstanceOf(Resource::class, $scales);
        static::assertSame(1000, $scales->stack->value);
        static::assertInstanceOf(Boost::class, $boost);
        static::assertSame(1, $boost->stack->value);
    }

    #[Test]
    public function an_item_the_game_marks_as_temporary_is_tagged_so(): void
    {
        $ore = ItemKinds::read([
            'ItemID' => 42_658,
            'sName' => 'Metal Ore',
            'sType' => 'Quest Item',
            'iLvl' => 1,
            'bTemp' => true,
        ], InventoryLoadedEvent::class);

        static::assertTrue($ore->tags->has(ItemTag::Temporary));
        static::assertFalse(self::read('Item')->tags->has(ItemTag::Temporary));
    }

    #[Test]
    public function it_keeps_the_id_and_name_of_the_item(): void
    {
        $item = self::read('Sword');

        static::assertSame(1, $item->id->value);
        static::assertSame('Hero Item', $item->name->value);
    }

    #[Test]
    public function it_rejects_unknown_item_types_in_the_name_of_the_event(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage(InventoryLoadedEvent::class . ": unknown item type 'Spaceship'");

        self::read('Spaceship');
    }

    #[Test]
    public function an_item_without_its_level_cannot_be_read(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('an item has no id, name or level');

        ItemKinds::read(['ItemID' => 1, 'sName' => 'Hero Item', 'sType' => 'Sword'], InventoryLoadedEvent::class);
    }

    #[Test]
    public function an_item_of_level_zero_is_read_as_level_one(): void
    {
        $item = ItemKinds::read([
            'ItemID' => 10_550,
            'sName' => "Holy Fire's Gaze",
            'sType' => 'Helm',
            'iLvl' => 0,
            'sES' => 'he',
            'EnhLvl' => 1,
            'EnhRty' => 1,
            'EnhPatternID' => 1,
        ], InventoryLoadedEvent::class);

        static::assertInstanceOf(Helm::class, $item);
        static::assertSame(1, $item->level->value);
    }

    #[Test]
    public function a_weapon_whose_damage_varies_out_of_range_cannot_be_read(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('weapon 1 varies its damage by an invalid amount');

        ItemKinds::read([
            'ItemID' => 1,
            'sName' => 'Hero Item',
            'sType' => 'Sword',
            'iLvl' => 1,
            'iRng' => 101,
        ], InventoryLoadedEvent::class);
    }

    private static function read(string $type): Item
    {
        return ItemKinds::read([
            'ItemID' => 1,
            'sName' => 'Hero Item',
            'sType' => $type,
            'iLvl' => 1,
        ], InventoryLoadedEvent::class);
    }
}
