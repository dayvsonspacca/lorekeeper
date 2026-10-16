<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Shop;

use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\Type;
use Lorekeeper\Server\Event\Shop\ShopLoadedCost;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class ShopLoadedEventTest extends TestCase
{
    private const string ASSORTED_SHOP = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadShop","shopinfo":{"ShopID":31,"sName":"Battleon Assorted shop","bStaff":false,"bUpgrd":false,"bMerge":false,"bHouse":false,"sField":"","iIndex":-1,"Location":"Menu","gameFlag":1,"FactionID":1,"iReqRep":0,"items":[{"ShopItemID":1629,"iQty":1,"iQtyRemain":-1,"ItemID":1235,"sName":"Lovely Fan","sDesc":"Dress wearing martial artists will look great while sawing things in half with this lethal fan. Also good for cooling yourself off during the summer heat.","iRng":10,"iRty":12,"iCost":5000,"iLvl":1,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":false,"bStaff":false,"sFile":"items/daggers/Fan01.swf","sLink":"","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Dagger","sIcon":"iwdagger","sES":"Weapon","sElmt":"None","EnhID":1856,"EnhRty":1,"EnhLvl":1,"EnhPatternID":1,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1},{"ShopItemID":10263,"iQty":1,"iQtyRemain":-1,"ItemID":16416,"sName":"Gladiator of Circumstance","sDesc":"Ready your battle stance as the Gladiator of Circumstance! Are you not impressed by the sharpness of my blade? Perhaps you would like a demonstration?","iRng":10,"iRty":13,"iCost":450,"iLvl":1,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"GladiatorOfCircumstance.swf","sLink":"GladiatorOfCircumstance","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Armor","sIcon":"iwarmor","sES":"co","sElmt":"None","EnhID":0,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1},{"ShopItemID":10102,"iQty":1,"iQtyRemain":-1,"ItemID":16118,"sName":"Treasure Map","sDesc":"Use this clue to find an exclusive in-game shop: \r\nWhen doom and darkness stalk the land, \r\nWhat force would dare to take a stand? \r\nTo keep Lore safe from evil's stare, \r\nseek ordered ranks and the bright light's glare.\r\n(Can't be sold or refunded.) ","iRng":10,"iRty":100,"iCost":2000,"iLvl":1,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":true,"bStaff":false,"sLink":"","sMeta":"NoSell","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Item","sIcon":"iibag","sES":"None","sElmt":"None","EnhID":0,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1}]}}}}
        JSON;

    private const string MERGE_SHOP = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadShop","shopinfo":{"ShopID":609,"sName":"Reward Gem Shop","bStaff":false,"bUpgrd":false,"bMerge":true,"bHouse":false,"sField":"","iIndex":-1,"Location":"Menu","gameFlag":1,"FactionID":1,"iReqRep":0,"items":[{"ShopItemID":1188,"iQty":1,"iQtyRemain":-1,"ItemID":16963,"sName":"Realm Guardian","sDesc":"You have defended the area outside Swordhaven valiantly! King Alteon the Good thanks you, and wishes to reward you with this armor.","iRng":10,"iRty":13,"iCost":0,"iLvl":1,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"TheRegal.swf","sLink":"TheRegal","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Armor","sIcon":"iwarmor","sES":"co","sElmt":"None","EnhID":0,"iDPS":100,"MergeID":1766,"turnin":[{"ItemID":17109,"sName":"Realm Gem","iQty":15}],"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1},{"ShopItemID":1185,"iQty":1,"iQtyRemain":-1,"ItemID":17006,"sName":"Realm Guardian Cloak","sDesc":"You have defended the area outside Swordhaven valiantly! King Alteon the Good thanks you, and wishes to reward you with this cape.","iRng":10,"iRty":13,"iCost":0,"iLvl":1,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/capes/TheRegalCape.swf","sLink":"TheRegalCape","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Cape","sIcon":"iicape","sES":"ba","sElmt":"None","EnhID":0,"iDPS":100,"MergeID":1762,"turnin":[{"ItemID":17109,"sName":"Realm Gem","iQty":5}],"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1},{"ShopItemID":1186,"iQty":1,"iQtyRemain":-1,"ItemID":16964,"sName":"Realm Guardian Helm","sDesc":"You have defended the area outside Swordhaven valiantly! King Alteon the Good thanks you, and wishes to reward you with this helm.","iRng":10,"iRty":13,"iCost":0,"iLvl":1,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/helms/TheRegalHelm.swf","sLink":"TheRegalHelm","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Helm","sIcon":"iihelm","sES":"he","sElmt":"None","EnhID":0,"iDPS":100,"MergeID":1763,"turnin":[{"ItemID":17109,"sName":"Realm Gem","iQty":7}],"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1},{"ShopItemID":10070,"iQty":1,"iQtyRemain":-1,"ItemID":17185,"sName":"CLASS Boost! (20 min)","sDesc":"Using this item will DOUBLE all class rank points gained from killing monsters or completing quests for 20 minutes of in-game play time. Does not expire while logged out.","iRng":10,"iRty":13,"iCost":0,"iLvl":1,"bTemp":false,"iStk":9999,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"icbcp","sLink":"cpboost::20::false","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"ServerUse","sIcon":"iicrystal","sES":"None","sElmt":"None","EnhID":0,"iDPS":100,"MergeID":1764,"turnin":[{"ItemID":17109,"sName":"Realm Gem","iQty":3}],"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1},{"ShopItemID":1187,"iQty":1,"iQtyRemain":-1,"ItemID":17134,"sName":"Realm Guardian Blade","sDesc":"You have defended the area outside Swordhaven valiantly! King Alteon the Good thanks you, and wishes to reward you with this sword.","iRng":10,"iRty":13,"iCost":0,"iLvl":1,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/swords/TheRegalBlade.swf","sLink":"TheRegalBlade","iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Sword","sIcon":"iwsword","sES":"Weapon","sElmt":"None","EnhID":0,"iDPS":100,"MergeID":1765,"turnin":[{"ItemID":17109,"sName":"Realm Gem","iQty":10}],"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1}]}}}}
        JSON;

    #[Test]
    public function it_reads_a_real_shop(): void
    {
        $shop = self::load(self::ASSORTED_SHOP);
        [$fan, $armor, $map] = $shop->items;

        static::assertSame(31, $shop->id->value);
        static::assertSame('Battleon Assorted shop', $shop->name->value);
        static::assertFalse($shop->memberOnly);

        static::assertSame('Lovely Fan', $fan->name->value);
        static::assertSame(Type\Weapon::Dagger, $fan->detail);
        static::assertSame(Type\Category::Equipment, $fan->category);
        static::assertSame(EquipmentSlot::Weapon, $fan->slot->unwrap());
        static::assertInstanceOf(Gold::class, $fan->price->unwrap()->amount);
        static::assertSame(5000, $fan->price->unwrap()->amount->value);
        static::assertSame('items/daggers/Fan01.swf', $fan->file->unwrap()->value);
        static::assertTrue($fan->link->isNone());
        static::assertSame([], $fan->costs);

        static::assertInstanceOf(AdventureCoins::class, $armor->price->unwrap()->amount);
        static::assertSame('GladiatorOfCircumstance', $armor->link->unwrap()->value);

        static::assertSame(Type\Misc::Generic, $map->detail);
        static::assertTrue($map->slot->isNone());
        static::assertTrue($map->file->isNone());
    }

    #[Test]
    public function it_reads_the_merge_costs_of_a_real_merge_shop(): void
    {
        $shop = self::load(self::MERGE_SHOP);

        static::assertSame('Reward Gem Shop', $shop->name->value);
        static::assertSame(
            [15, 5, 7, 3, 10],
            array_map(static fn($item): int => $item->costs[0]->cost->quantity, $shop->items),
        );

        $cost = $shop->items[0]->costs[0];
        static::assertInstanceOf(ShopLoadedCost::class, $cost);
        static::assertSame(17_109, $cost->cost->itemId->value);
        static::assertSame('Realm Gem', $cost->name->value);
        static::assertTrue($shop->items[0]->price->isNone());
        static::assertSame(Type\Misc::Boost, $shop->items[3]->detail);
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
        static::assertSame($detail, self::load(self::shop($type, 'None'))->items[0]->detail);
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
        static::assertSame($slot, self::load(self::shop('Armor', $code))->items[0]->slot->unwrap());
    }

    #[Test]
    public function it_rejects_unknown_item_types(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown item type 'Spaceship'");

        self::load(self::shop('Spaceship', 'None'));
    }

    #[Test]
    public function it_rejects_unknown_equipment_slots(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown equipment slot 'xx'");

        self::load(self::shop('Armor', 'xx'));
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(
            ShopLoadedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"loadInventoryBig"}}}')->unwrap())->isNone(),
        );
    }

    #[Test]
    public function it_rejects_messages_without_shopinfo(): void
    {
        $this->expectException(MalformedEventException::class);

        ShopLoadedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"loadShop"}}}')->unwrap());
    }

    private static function load(string $message): ShopLoadedEvent
    {
        return ShopLoadedEvent::from(JsonMessage::from($message)->unwrap())->unwrap();
    }

    private static function shop(string $type, string $slot): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'loadShop',
            'shopinfo' => [
                'ShopID' => 1,
                'sName' => 'Hero Shop',
                'bUpgrd' => true,
                'items' => [['ItemID' => 1, 'sName' => 'Hero Item', 'sType' => $type, 'sES' => $slot]],
            ],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
