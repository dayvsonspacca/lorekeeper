<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps\Gear;

use Lorekeeper\Bot\Steps\Gear\Enhance;
use Lorekeeper\Bot\Tests\Steps\InOrder;
use Lorekeeper\Bot\Tests\Steps\Play;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerTag;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\ActionTooSoonEvent;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnhanceTest extends TestCase
{
    private const string LUCKY_SHOP = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadShop","shopinfo":{"ShopID":147,"sName":"Lucky Enhancement Shop","bStaff":false,"bUpgrd":false,"bMerge":false,"bHouse":false,"sField":"","iIndex":-1,"Location":"Menu","gameFlag":1,"FactionID":1,"iReqRep":0,"items":[{"ShopItemID":3306,"iQty":1,"iQtyRemain":-1,"ItemID":3657,"sName":"Lucky Helm Enhancement","sDesc":"","iRng":10,"iRty":1,"iCost":960,"iLvl":20,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","PatternID":9,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Enhancement","sIcon":"none","sES":"he","sElmt":"None","EnhID":0,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1,"bEnhShop":true},{"ShopItemID":3277,"iQty":1,"iQtyRemain":-1,"ItemID":3628,"sName":"Lucky Weapon Enhancement","sDesc":"","iRng":10,"iRty":1,"iCost":1010,"iLvl":21,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","PatternID":9,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Enhancement","sIcon":"none","sES":"Weapon","sElmt":"None","EnhID":0,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1,"bEnhShop":true},{"ShopItemID":3281,"iQty":1,"iQtyRemain":-1,"ItemID":3632,"sName":"Lucky Weapon Enhancement","sDesc":"","iRng":10,"iRty":1,"iCost":1210,"iLvl":25,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","PatternID":9,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Enhancement","sIcon":"none","sES":"Weapon","sElmt":"None","EnhID":0,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1,"bEnhShop":true},{"ShopItemID":3372,"iQty":1,"iQtyRemain":-1,"ItemID":3723,"sName":"Lucky Armor Enhancement","sDesc":"","iRng":10,"iRty":1,"iCost":1260,"iLvl":26,"bTemp":false,"iStk":1,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","PatternID":9,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Enhancement","sIcon":"none","sES":"ar","sElmt":"None","EnhID":0,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1,"bEnhShop":true},{"ShopItemID":4121,"iQty":1,"iQtyRemain":-1,"ItemID":4505,"sName":"Lucky Weapon Enhancement","sDesc":"","iRng":10,"iRty":2,"iCost":2260,"iLvl":23,"bTemp":false,"iStk":1,"bUpg":true,"bCoins":false,"bStaff":false,"sLink":"","PatternID":9,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iQSindex":-1,"iQSvalue":0,"bHouse":false,"sType":"Enhancement","sIcon":"none","sES":"Weapon","sElmt":"None","EnhID":0,"iDPS":100,"TokID":0,"TokQty":0,"bHide":0,"bSilhouette":0,"SortIndex":-1}],"filterType":"spiral1","oIndex":3}}}}
        JSON;

    private const string INVENTORY = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":3,"sName":"Default Staff","sType":"Staff","iLvl":1,"iRng":10,"iQty":1,"bUpg":false,"bEquip":true,"EnhID":1856,"EnhLvl":1,"EnhPatternID":1},{"ItemID":9392,"sName":"This Might Be A Token","iLvl":1,"sType":"Item","iQty":1,"bUpg":false,"bEquip":false,"EnhID":null,"EnhLvl":null,"EnhPatternID":null},{"ItemID":15653,"sName":"Mage","iLvl":1,"sType":"Class","iQty":302500,"bUpg":false,"bEquip":false,"EnhID":1959,"EnhLvl":1,"EnhPatternID":1},{"ItemID":20662,"sName":"Oracle","iLvl":1,"sType":"Class","iQty":302500,"bUpg":false,"bEquip":true,"EnhID":8283,"EnhLvl":1,"EnhPatternID":1},{"ItemID":42657,"sName":"Crypto Token","iLvl":1,"sType":"Item","iQty":2,"bUpg":false,"bEquip":false,"EnhID":null,"EnhLvl":null,"EnhPatternID":null},{"ItemID":45739,"sName":"Gear of Doom","iLvl":1,"sType":"Resource","iQty":3,"bUpg":false,"bEquip":false,"EnhID":null,"EnhLvl":null,"EnhPatternID":null}]}}}
        JSON;

    private const string BLINDING_LIGHT = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"loadInventoryBig","items":[{"ItemID":37207,"sName":"Dual Blinding Light of Destiny","sType":"Dagger","sES":"Weapon","iLvl":1,"iRty":35,"iRng":0,"iQty":1,"bUpg":false,"bEquip":true,"EnhID":0,"EnhLvl":80,"EnhRty":5,"EnhPatternID":10,"ProcID":7}]}}}
        JSON;

    private const string ENHANCED = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"enhanceItemShop","ItemIDs":[3],"EnhID":3628,"EnhPID":9,"EnhLvl":21,"EnhRty":1,"EnhDPS":100,"EnhRng":10,"EnhName":"Lucky Weapon Enhancement","iCost":1010,"bSuccess":1}}}
        JSON;

    #[Test]
    public function it_buys_the_strongest_lucky_enhancement_the_character_can_use_for_each_equipped_item(): void
    {
        $me = self::Hero();

        [$steps, $result] = Play::run(self::enhance(EnhancementType::Lucky), $me, [
            self::shop(),
            ItemsEnhancedEvent::from(JsonMessage::from(self::ENHANCED)->unwrap())->unwrap(),
        ]);

        static::assertSame(['%xt%zm%loadShop%1%147%', '%xt%zm%enhanceItemShop%1%3%3628%147%'], Play::sent($steps));
        static::assertEquals(new Done('all steps are done'), $result);
        $staff = $me->player()->inventory->permanent->find(new ItemId(3))->unwrap();
        static::assertInstanceOf(Weapon::class, $staff->item);
        static::assertSame(EnhancementType::Lucky, $staff->item->enhancement->unwrap()->type);
        static::assertSame(35_178 - 1010, $me->player()->wallet->gold->value);
    }

    #[Test]
    public function it_waits_and_asks_again_when_the_game_says_it_was_too_soon(): void
    {
        $me = self::Hero();

        [$steps, $result] = Play::run(self::enhance(EnhancementType::Lucky), $me, [
            self::shop(),
            ActionTooSoonEvent::from(
                DelimitedMessage::from('%xt%warning%-1%Please slow down. Last action was too soon!%')->unwrap(),
            )->unwrap(),
            null,
            ItemsEnhancedEvent::from(JsonMessage::from(self::ENHANCED)->unwrap())->unwrap(),
        ]);

        static::assertSame(
            ['%xt%zm%loadShop%1%147%', '%xt%zm%enhanceItemShop%1%3%3628%147%', '%xt%zm%enhanceItemShop%1%3%3628%147%'],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function gear_that_already_has_the_best_enhancement_is_left_alone(): void
    {
        $me = self::Hero();
        $me->hear(ItemsEnhancedEvent::from(JsonMessage::from(self::ENHANCED)->unwrap())->unwrap());

        [$steps, $result] = Play::run(self::enhance(EnhancementType::Lucky), $me, [self::shop()]);

        static::assertSame(['%xt%zm%loadShop%1%147%'], Play::sent($steps));
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function gear_with_an_enhancement_of_an_unhandled_type_is_left_alone(): void
    {
        $me = self::Hero();
        $me->hear(InventoryLoadedEvent::from(JsonMessage::from(self::BLINDING_LIGHT)->unwrap())->unwrap());

        [$steps, $result] = Play::run(self::enhance(EnhancementType::Lucky), $me, [self::shop()]);

        static::assertSame(['%xt%zm%loadShop%1%147%'], Play::sent($steps));
        static::assertEquals(new Done('all steps are done'), $result);
    }

    #[Test]
    public function a_member_takes_the_member_enhancement_of_the_highest_level_it_can_use(): void
    {
        $me = self::Hero();
        $me->player()->tags->add(PlayerTag::Member);

        [$steps] = Play::run(self::enhance(EnhancementType::Lucky), $me, [self::shop(), null]);

        static::assertSame('%xt%zm%enhanceItemShop%1%3%4505%147%', Play::sent($steps)[1]);
    }

    #[Test]
    public function types_no_shop_sells_are_refused(): void
    {
        [$steps, $result] = Play::run(self::enhance(EnhancementType::Adventurer), self::Hero(), []);

        static::assertEquals(
            new Failed('step 1 (Enhance the equipped gear with Adventurer): no shop sells Adventurer enhancements'),
            $result,
        );
        static::assertSame([], Play::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_shop_does_not_open(): void
    {
        [$steps, $result] = Play::run(self::enhance(EnhancementType::Lucky), self::Hero(), [null]);

        static::assertEquals(
            new Failed('step 1 (Enhance the equipped gear with Lucky): the Lucky enhancement shop did not open'),
            $result,
        );
        static::assertSame(['%xt%zm%loadShop%1%147%'], Play::sent($steps));
    }

    private static function Hero(): Character
    {
        $me = Fixture::me(level: 23);
        $me->hear(InventoryLoadedEvent::from(JsonMessage::from(self::INVENTORY)->unwrap())->unwrap());
        $me->hear(
            new MonsterRewardedEvent(new Gold(35_178), new Experience(0), new ClassPoints(0), new MonsterSpawnId(1)),
        );

        return $me;
    }

    private static function shop(): ShopLoadedEvent
    {
        return ShopLoadedEvent::from(JsonMessage::from(self::LUCKY_SHOP)->unwrap())->unwrap();
    }

    private static function enhance(EnhancementType $type): InOrder
    {
        return new InOrder([new Enhance($type)]);
    }
}
