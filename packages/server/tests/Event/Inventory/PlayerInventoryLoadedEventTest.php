<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Type;
use Lorekeeper\Server\Event\Inventory\InventoryItem;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class PlayerInventoryLoadedEventTest extends TestCase
{
    private const string INVENTORY = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":4767,"sName":"Unidentified 20","sDesc":"Nulgath slayed a dragon and crafted this sword form it's very bones. Congratulations on winning the \"Dragonbone Blade\"!","iRng":10,"iDPS":0,"iLvl":1,"iRty":12,"iCost":100,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sFile":"items/swords/MiltonPoolWheelsword01.swf","sLink":"MiltonPoolWheelsword01","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","EnhPatternID":1,"bHouse":false,"sType":"Sword","sIcon":"iwsword","sES":"Weapon","sElmt":"None","metaValues":{},"bQuest":1,"CharItemID":701150928,"iQty":1,"bEquip":false,"EnhID":1936,"iHrs":22312,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0,"iEnh":1936,"EnhLvl":81,"EnhRty":1,"EnhRng":10,"EnhDPS":100,"bWear":0},{"ItemID":31660,"sName":"Dark Sun Mage","sDesc":"The Dark Sun Magi use their powers not necessarily for Evil but they do not fight for Good.","iRng":10,"iDPS":0,"iLvl":1,"iRty":100,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"DarkMage.swf","sLink":"DarkMage","sMeta":"cp:1.25,rep:1.25,gold:1.25,exp:1.25","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Armor","sIcon":"iwarmor","sES":"co","sElmt":"None","metaValues":{"cp":1,"rep":1,"gold":1,"exp":1},"CharItemID":695295346,"iQty":1,"bEquip":true,"EnhID":0,"iHrs":22501,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0,"bWear":0},{"ItemID":15651,"sName":"Healer","sDesc":"Recommended enhancement: Healer. Healers are servants of good who use their powers to aid the sick, weak, and injured. Their powerful healing magic is often the difference between a group's victory or doom.","iRng":10,"iDPS":0,"iLvl":1,"iRty":12,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"NewHealerR2.swf","sLink":"NewHealerB2","sMeta":"17","ClassID":17,"iQSIndex":-1,"iQSValue":0,"sReqQuests":"","EnhPatternID":9,"bHouse":false,"sType":"Class","sIcon":"iiclass","sES":"ar","sElmt":"None","metaValues":{"ref":17},"CharItemID":695648125,"iQty":177790,"bEquip":false,"EnhID":40014,"iHrs":22489,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0,"iEnh":40014,"EnhLvl":81,"EnhRty":5,"EnhRng":10,"EnhDPS":0},{"ItemID":22884,"sName":"Gold PvP Amulet +1500","sDesc":"Equip this Amulet to gain an additional 1500 Health while in Player vs Player (PvP) battlegrounds.","iRng":10,"iDPS":0,"iLvl":20,"iRty":13,"iCost":26000,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"1500","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Necklace","sIcon":"iin1","sES":"am","sElmt":"None","metaValues":{},"CharItemID":101673789,"iQty":1,"bEquip":true,"EnhID":0,"iHrs":75307,"tPurchase":"0","bBank":0},{"ItemID":4861,"sName":"Voucher of Nulgath","sDesc":"This is good for certain items of Nulgath and can also be used for a variety of other purchases. You may only own 1 voucher at a time.","iRng":10,"iDPS":0,"iLvl":1,"iRty":19,"iCost":1000000,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":true,"bCoins":false,"bStaff":false,"sLink":"","sMeta":"AutoAdd","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Resource","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{"autoadd":true},"bQuest":1,"CharItemID":697462996,"iQty":1,"bEquip":false,"EnhID":0,"iHrs":22432,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0}]}}}
        JSON;

    #[Test]
    public function it_reads_the_equipment_of_a_real_inventory(): void
    {
        [$sword, $armor, $class] = self::load(self::INVENTORY);

        static::assertSame('Unidentified 20', $sword->name->value);
        static::assertSame(Type\Weapon::Sword, $sword->detail);
        static::assertSame(Type\ItemCategory::Equipment, $sword->category);
        static::assertSame(EquipmentSlot::Weapon, $sword->slot->unwrap());
        static::assertSame(100, $sword->price->unwrap()->amount->value);
        static::assertSame('items/swords/MiltonPoolWheelsword01.swf', $sword->file->unwrap()->value);
        static::assertSame('MiltonPoolWheelsword01', $sword->link->unwrap()->value);
        static::assertStringStartsWith('Nulgath slayed a dragon', $sword->description->unwrap()->value);
        static::assertFalse($sword->memberOnly);

        static::assertSame(Type\Equipment::Armor, $armor->detail);
        static::assertSame(Type\Equipment::CharacterClass, $class->detail);
        static::assertSame(EquipmentSlot::Armor, $armor->slot->unwrap());
        static::assertSame(EquipmentSlot::Armor, $class->slot->unwrap());
        static::assertTrue($armor->price->isNone());
    }

    #[Test]
    public function it_reads_items_without_file_or_slot(): void
    {
        [, , , $necklace, $voucher] = self::load(self::INVENTORY);

        static::assertSame(Type\Equipment::Necklace, $necklace->detail);
        static::assertSame(EquipmentSlot::Necklace, $necklace->slot->unwrap());
        static::assertTrue($necklace->file->isNone());
        static::assertTrue($necklace->link->isNone());

        static::assertSame(Type\Misc::Generic, $voucher->detail);
        static::assertSame(Type\ItemCategory::Misc, $voucher->category);
        static::assertTrue($voucher->slot->isNone());
        static::assertSame(1_000_000, $voucher->price->unwrap()->amount->value);
        static::assertTrue($voucher->memberOnly);
    }

    #[Test]
    #[TestWith(['Armor', Type\Equipment::Armor])]
    #[TestWith(['Cape', Type\Equipment::Cape])]
    #[TestWith(['Class', Type\Equipment::CharacterClass])]
    #[TestWith(['Misc', Type\Equipment::Ground])]
    #[TestWith(['Helm', Type\Equipment::Helm])]
    #[TestWith(['Necklace', Type\Equipment::Necklace])]
    #[TestWith(['Pet', Type\Equipment::Pet])]
    #[TestWith(['Axe', Type\Weapon::Axe])]
    #[TestWith(['Bow', Type\Weapon::Bow])]
    #[TestWith(['Dagger', Type\Weapon::Dagger])]
    #[TestWith(['Gauntlet', Type\Weapon::Gauntlet])]
    #[TestWith(['Gun', Type\Weapon::Gun])]
    #[TestWith(['HandGun', Type\Weapon::HandGun])]
    #[TestWith(['Mace', Type\Weapon::Mace])]
    #[TestWith(['Polearm', Type\Weapon::Polearm])]
    #[TestWith(['Rifle', Type\Weapon::Rifle])]
    #[TestWith(['Staff', Type\Weapon::Staff])]
    #[TestWith(['Sword', Type\Weapon::Sword])]
    #[TestWith(['Wand', Type\Weapon::Wand])]
    #[TestWith(['Whip', Type\Weapon::Whip])]
    #[TestWith(['Floor Item', Type\House::Floor])]
    #[TestWith(['House', Type\House::House])]
    #[TestWith(['Wall Item', Type\House::Wall])]
    #[TestWith(['ServerUse', Type\Misc::Boost])]
    #[TestWith(['Enhancement', Type\Misc::Enhancement])]
    #[TestWith(['Item', Type\Misc::Generic])]
    #[TestWith(['Note', Type\Misc::Generic])]
    #[TestWith(['Quest Item', Type\Misc::Generic])]
    #[TestWith(['Resource', Type\Misc::Generic])]
    public function it_translates_the_item_type(
        string $type,
        Type\Equipment|Type\Weapon|Type\House|Type\Misc $detail,
    ): void {
        static::assertSame($detail, self::load(self::item($type, 'None'))[0]->detail);
    }

    #[Test]
    #[TestWith(['Weapon', EquipmentSlot::Weapon])]
    #[TestWith(['ar', EquipmentSlot::Armor])]
    #[TestWith(['co', EquipmentSlot::Armor])]
    #[TestWith(['he', EquipmentSlot::Helm])]
    #[TestWith(['ba', EquipmentSlot::Cape])]
    #[TestWith(['pe', EquipmentSlot::Pet])]
    #[TestWith(['mi', EquipmentSlot::Ground])]
    #[TestWith(['am', EquipmentSlot::Necklace])]
    public function it_translates_the_equipment_slot(string $code, EquipmentSlot $slot): void
    {
        static::assertSame($slot, self::load(self::item('Armor', $code))[0]->slot->unwrap());
    }

    #[Test]
    public function it_rejects_unknown_item_types(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown item type 'Spaceship'");

        self::load(self::item('Spaceship', 'None'));
    }

    #[Test]
    public function it_rejects_unknown_equipment_slots(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown equipment slot 'xx'");

        self::load(self::item('Armor', 'xx'));
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            PlayerInventoryLoadedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"loadShop"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_items(): void
    {
        $this->expectException(MalformedEventException::class);

        PlayerInventoryLoadedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"loadInventoryBig"}}}')->unwrap());
    }

    /** @return list<InventoryItem> */
    private static function load(string $message): array
    {
        return PlayerInventoryLoadedEvent::from(JsonMessage::from($message)->unwrap())->unwrap()->items;
    }

    private static function item(string $type, string $slot): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'loadInventoryBig',
            'items' => [
                ['ItemID' => 1, 'sName' => 'Hero Item', 'sType' => $type, 'sES' => $slot],
            ],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
