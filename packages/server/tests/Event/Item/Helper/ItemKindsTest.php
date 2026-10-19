<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Item\Helper;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon;
use Lorekeeper\Core\Item\Equipment\WeaponType;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\House\HouseItemType;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Enhancement;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Exception\MalformedEventException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

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
    #[TestWith(['Enhancement', Enhancement::class])]
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
        $this->expectExceptionMessage(PlayerInventoryLoadedEvent::class . ": unknown item type 'Spaceship'");

        self::read('Spaceship');
    }

    private static function read(string $type): Item
    {
        return ItemKinds::read(new ItemId(1), new ItemName('Hero Item'), $type, PlayerInventoryLoadedEvent::class);
    }
}
