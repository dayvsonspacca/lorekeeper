<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Shop;

use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\World\Shop\CurrencyShop;
use Lorekeeper\Core\World\Shop\CurrencyShopItem;
use Lorekeeper\Core\World\Shop\MergeShop;
use Lorekeeper\Core\World\Shop\MergeShopItem;
use Lorekeeper\Core\World\Shop\ShopTag;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
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
    public function it_reads_a_real_shop_that_sells_for_gold_and_adventure_coins(): void
    {
        $event = self::load(self::ASSORTED_SHOP);
        $shop = $event->shop;

        static::assertInstanceOf(CurrencyShop::class, $shop);
        static::assertSame(31, $shop->id->value);
        static::assertSame('Battleon Assorted shop', $shop->name->value);
        static::assertFalse($shop->tags->has(ShopTag::MemberOnly));
        static::assertFalse($shop->tags->has(ShopTag::House));

        /** @var list<CurrencyShopItem> $items */
        $items = array_values($shop->inventory->items->toArray());
        [$fan, $armor, $map] = $items;

        static::assertInstanceOf(Weapon::class, $fan->item);
        static::assertSame(WeaponType::Dagger, $fan->item->type);
        static::assertSame('Lovely Fan', $fan->item->name->value);
        static::assertInstanceOf(Gold::class, $fan->price->amount);
        static::assertSame(5000, $fan->price->amount->value);
        static::assertInstanceOf(Armor::class, $armor->item);
        static::assertInstanceOf(AdventureCoins::class, $armor->price->amount);
        static::assertSame(450, $armor->price->amount->value);
        static::assertInstanceOf(Resource::class, $map->item);
        static::assertFalse($map->item->tags->has(ItemTag::MemberOnly));
        static::assertSame('Battleon Assorted shop: 3 items', $event->summary());
    }

    #[Test]
    public function it_reads_a_real_merge_shop(): void
    {
        $event = self::load(self::MERGE_SHOP);
        $shop = $event->shop;

        static::assertInstanceOf(MergeShop::class, $shop);
        static::assertSame('Reward Gem Shop', $shop->name->value);

        /** @var list<MergeShopItem> $items */
        $items = array_values($shop->inventory->items->toArray());

        static::assertSame(
            [15, 5, 7, 3, 10],
            array_map(static fn(MergeShopItem $item): int => $item->costs[0]->quantity->value, $items),
        );
        static::assertSame(17_109, $items[0]->costs[0]->itemId->value);
        static::assertInstanceOf(Boost::class, $items[3]->item);
        static::assertSame('Reward Gem Shop: 5 items', $event->summary());
    }

    #[Test]
    public function it_tags_the_items_only_members_can_buy(): void
    {
        $shop = self::load(self::shop(item: ['bUpg' => true]))->shop;

        static::assertInstanceOf(CurrencyShop::class, $shop);
        /** @var list<CurrencyShopItem> $items */
        $items = array_values($shop->inventory->items->toArray());
        static::assertTrue($items[0]->item->tags->has(ItemTag::MemberOnly));
    }

    #[Test]
    public function it_tags_shops_for_members_and_for_houses(): void
    {
        $shop = self::load(self::shop(['bUpgrd' => true, 'bHouse' => true]))->shop;

        static::assertTrue($shop->tags->has(ShopTag::MemberOnly));
        static::assertTrue($shop->tags->has(ShopTag::House));
    }

    #[Test]
    public function it_rejects_unknown_item_types(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage("unknown item type 'Spaceship'");

        self::load(self::shop(type: 'Spaceship'));
    }

    #[Test]
    public function it_rejects_a_merge_that_costs_nothing(): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage('Merging Hero Item costs nothing.');

        self::load(self::shop(['bMerge' => true]));
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

    /**
     * @param array<string, bool> $flags
     * @param array<string, bool> $item
     */
    private static function shop(array $flags = [], string $type = 'Armor', array $item = []): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'loadShop',
            'shopinfo' => [
                'ShopID' => 1,
                'sName' => 'Hero Shop',
                ...$flags,
                'items' => [[
                    'ItemID' => 1,
                    'sName' => 'Hero Item',
                    'sType' => $type,
                    'iLvl' => 1,
                    'iCost' => 0,
                    ...$item,
                ]],
            ],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
