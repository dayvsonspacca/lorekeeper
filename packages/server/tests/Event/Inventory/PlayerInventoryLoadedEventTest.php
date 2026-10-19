<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Weapon;
use Lorekeeper\Core\Item\Equipment\WeaponType;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlayerInventoryLoadedEventTest extends TestCase
{
    private const string INVENTORY = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":4767,"sName":"Unidentified 20","sDesc":"Nulgath slayed a dragon and crafted this sword form it's very bones. Congratulations on winning the \"Dragonbone Blade\"!","iRng":10,"iDPS":0,"iLvl":1,"iRty":12,"iCost":100,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sFile":"items/swords/MiltonPoolWheelsword01.swf","sLink":"MiltonPoolWheelsword01","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","EnhPatternID":1,"bHouse":false,"sType":"Sword","sIcon":"iwsword","sES":"Weapon","sElmt":"None","metaValues":{},"bQuest":1,"CharItemID":701150928,"iQty":1,"bEquip":false,"EnhID":1936,"iHrs":22312,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0,"iEnh":1936,"EnhLvl":81,"EnhRty":1,"EnhRng":10,"EnhDPS":100,"bWear":0},{"ItemID":31660,"sName":"Dark Sun Mage","sDesc":"The Dark Sun Magi use their powers not necessarily for Evil but they do not fight for Good.","iRng":10,"iDPS":0,"iLvl":1,"iRty":100,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"DarkMage.swf","sLink":"DarkMage","sMeta":"cp:1.25,rep:1.25,gold:1.25,exp:1.25","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Armor","sIcon":"iwarmor","sES":"co","sElmt":"None","metaValues":{"cp":1,"rep":1,"gold":1,"exp":1},"CharItemID":695295346,"iQty":1,"bEquip":true,"EnhID":0,"iHrs":22501,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0,"bWear":0},{"ItemID":15651,"sName":"Healer","sDesc":"Recommended enhancement: Healer. Healers are servants of good who use their powers to aid the sick, weak, and injured. Their powerful healing magic is often the difference between a group's victory or doom.","iRng":10,"iDPS":0,"iLvl":1,"iRty":12,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"NewHealerR2.swf","sLink":"NewHealerB2","sMeta":"17","ClassID":17,"iQSIndex":-1,"iQSValue":0,"sReqQuests":"","EnhPatternID":9,"bHouse":false,"sType":"Class","sIcon":"iiclass","sES":"ar","sElmt":"None","metaValues":{"ref":17},"CharItemID":695648125,"iQty":177790,"bEquip":false,"EnhID":40014,"iHrs":22489,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0,"iEnh":40014,"EnhLvl":81,"EnhRty":5,"EnhRng":10,"EnhDPS":0},{"ItemID":22884,"sName":"Gold PvP Amulet +1500","sDesc":"Equip this Amulet to gain an additional 1500 Health while in Player vs Player (PvP) battlegrounds.","iRng":10,"iDPS":0,"iLvl":20,"iRty":13,"iCost":26000,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"1500","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Necklace","sIcon":"iin1","sES":"am","sElmt":"None","metaValues":{},"CharItemID":101673789,"iQty":1,"bEquip":true,"EnhID":0,"iHrs":75307,"tPurchase":"0","bBank":0},{"ItemID":4861,"sName":"Voucher of Nulgath","sDesc":"This is good for certain items of Nulgath and can also be used for a variety of other purchases. You may only own 1 voucher at a time.","iRng":10,"iDPS":0,"iLvl":1,"iRty":19,"iCost":1000000,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":true,"bCoins":false,"bStaff":false,"sLink":"","sMeta":"AutoAdd","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Resource","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{"autoadd":true},"bQuest":1,"CharItemID":697462996,"iQty":1,"bEquip":false,"EnhID":0,"iHrs":22432,"bExtra1":false,"bExtra2":false,"tPurchase":"0","bBank":0}]}}}
        JSON;

    #[Test]
    public function it_reads_each_item_of_a_real_inventory_as_its_kind(): void
    {
        [$sword, $armor, $class, $necklace, $voucher] = self::load(self::INVENTORY);

        static::assertInstanceOf(Weapon::class, $sword->item);
        static::assertSame(WeaponType::Sword, $sword->item->type);
        static::assertSame(4767, $sword->item->id->value);
        static::assertSame('Unidentified 20', $sword->item->name->value);
        static::assertInstanceOf(Armor::class, $armor->item);
        static::assertInstanceOf(ClassItem::class, $class->item);
        static::assertInstanceOf(Necklace::class, $necklace->item);
        static::assertInstanceOf(Resource::class, $voucher->item);
    }

    #[Test]
    public function it_tags_the_items_only_members_can_use(): void
    {
        [$sword, , , , $voucher] = self::load(self::INVENTORY);

        static::assertFalse($sword->item->tags->has(ItemTag::MemberOnly));
        static::assertTrue($voucher->item->tags->has(ItemTag::MemberOnly));
    }

    #[Test]
    public function a_class_counts_once_though_its_quantity_holds_its_points(): void
    {
        [$sword, , $healer] = self::load(self::INVENTORY);

        static::assertSame(1, $sword->quantity->value);
        static::assertSame('Healer', $healer->item->name->value);
        static::assertSame(1, $healer->quantity->value);
    }

    #[Test]
    public function it_rejects_unknown_item_types(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown item type 'Spaceship'");

        self::load(self::item('Spaceship'));
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

    /** @return list<PlayerItem> */
    private static function load(string $message): array
    {
        return array_values(
            PlayerInventoryLoadedEvent::from(JsonMessage::from($message)->unwrap())
                ->unwrap()
                ->inventory->items->toArray(),
        );
    }

    private static function item(string $type): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'loadInventoryBig',
            'items' => [
                ['ItemID' => 1, 'sName' => 'Hero Item', 'sType' => $type],
            ],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
