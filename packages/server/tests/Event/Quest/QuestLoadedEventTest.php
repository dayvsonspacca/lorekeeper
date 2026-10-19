<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Quest;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\Type;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Quest\Objective\ItemObjective;
use Lorekeeper\Core\World\Quest\Requirement\ClassRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use Lorekeeper\Core\World\Quest\Requirement\MemberRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PlayerLevelRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use Lorekeeper\Core\World\Quest\Reward\DropRate;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ItemRewardType;
use Lorekeeper\Server\Event\Mention\Mention;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedQuest;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class QuestLoadedEventTest extends TestCase
{
    private const string QUESTS = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"getQuests","quests":{"39":{"QuestID":39,"sName":"Missing King","sDesc":"It seems that with all of the commotion going on in Noobshire, the King of the Rats has gone missing! Without their leader, the rats won't be able to restore order to their society! Locate the King Rat, so we can  restore their peaceful society. Check the Horc Noobs for a clue to where he is!","sEndText":"Great work - now we'll never run out of rats!","iLvl":1,"iExp":100,"iGold":100,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iRep":0,"iSlot":0,"iValue":7,"bUpg":false,"bOnce":true,"bStaff":false,"iWar":0,"bGuild":false,"turnin":[{"ItemID":325,"iQty":1},{"ItemID":15198,"iQty":1}],"reward":[{"ItemID":333,"iQty":1,"iType":0,"iRate":100}],"metaValues":{},"oRewards":{"itemsS":{"0":{"ItemID":333,"sName":"Recommendation Letter","sDesc":" ","iRng":1,"iDPS":0,"iLvl":1,"iRty":10,"iCost":400,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"1","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Note","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{},"iQty":1}}},"oItems":{"325":{"ItemID":325,"sName":"Rat Leader","sDesc":" ","iRng":1,"iDPS":100,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"RatKing","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{}},"15198":{"ItemID":15198,"sName":"Rat King Clue","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"39","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{}}}},"40":{"QuestID":40,"sName":"Take Down","sDesc":"The Horc Trainer appears to be VERY persistent… he's back, with a new group of Horc trainees. It's time for you to go out there and show him once and for all who is in charge around here! Defeat the Horc Trainer again, and bring me his pet rat as proof. His defeat should send a message to any Horcs who would even THINK of venturing back to Noobshire again! ","sEndText":"I'm going to send this rat back to Horctown... as a message. Don't mess with me!","iLvl":1,"iExp":200,"iGold":200,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iRep":0,"iSlot":0,"iValue":8,"bUpg":true,"bOnce":false,"bStaff":false,"iWar":0,"bGuild":false,"turnin":[{"ItemID":326,"iQty":1}],"metaValues":{},"oRewards":{},"oItems":{"326":{"ItemID":326,"sName":"Domesticated Rat","sDesc":" ","iRng":1,"iDPS":100,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"Orc Trainer","iQSIndex":-1,"iQSValue":0,"sReqQuests":"40","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{}}}},"342":{"QuestID":342,"sName":"What's Mine is Yours","sDesc":"The tracks in front of you lead to rare Gems that are incredibly valuable to the Dwarves. However, the multiple breaks and fallen debris on the tracks make for a completely unsafe work environment. If you want to prove yourself, here is your chance. Collect a Bag of Gems (30 Gems ) and 1 Chaotic Draconian Scale and bring them here.","sEndText":"Amazing! You have collected all 30 Gems… and there isn’t a scratch on you! You are the bravest Human I have ever met! Come back tomorrow and collect more Gems, will you? Your Reputation with Dwarfhold will increase drastically each time!            ","iLvl":0,"iExp":20000,"iGold":20000,"iClass":0,"iReqCP":0,"FactionID":2,"sFaction":"Dwarfhold","iReqRep":10000,"iRep":30000,"iSlot":-1,"iValue":0,"bUpg":false,"bOnce":false,"bStaff":false,"iWar":0,"iDaily":3,"sField":"id0","iIndex":3,"bGuild":false,"turnin":[{"ItemID":1626,"iQty":1},{"ItemID":8705,"iQty":1}],"metaValues":{},"oRewards":{},"oItems":{"1626":{"ItemID":1626,"sName":"Bag of Gems","sDesc":" ","iRng":10,"iDPS":100,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"Gems","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{}},"8705":{"ItemID":8705,"sName":"Chaotic Draconian Scale","sDesc":" ","iRng":1,"iDPS":0,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"342","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{}}}},"3295":{"QuestID":3295,"sName":"Strength","sDesc":"So, you're interested in becoming a member of my intrepid team of Loremaster explorers? Fantastic! But we'll need to test your skills to make sure you're ready! First, bring me 5 sets of camel tusks from the Bupers Camels in the Sandsea Oasis. If you can do that, I'll know you're strong enough to do this kind of work without getting yourself killed.","sEndText":"Oh, fantastic! Things are definitely looking hopeful!","iLvl":15,"iExp":200,"iGold":200,"iClass":0,"iReqCP":0,"FactionID":30,"sFaction":"Loremaster","iReqRep":0,"iRep":200,"iSlot":145,"iValue":1,"bUpg":false,"bOnce":false,"bStaff":false,"iWar":0,"bGuild":false,"turnin":[{"ItemID":22224,"iQty":5}],"metaValues":{},"oRewards":{},"oItems":{"22224":{"ItemID":22224,"sName":"Bupers Camel Tusks","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":5,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"3295","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{}}}},"3777":{"QuestID":3777,"sName":"Quest for the Bonebreaker","sDesc":"My pa told me tales of the Bonebreaker Cutlass. It's used by Undead Pirates and DoomKnight Commanders. Those who wield it gain great power. I'd LOVE to see what it looks like... and I'll give you a chance to get a prize! Bring me the Cutlass... pretty please?","sEndText":"Tee hee! Heroes LOVE pretty prizes... But I never know what I'll find when I And I can sure sell these blades to travelers. Come back tomorrow with another cutlass to try for another prize!","iLvl":0,"iExp":500,"iGold":500,"iClass":0,"iReqCP":0,"FactionID":1,"sFaction":"None","iReqRep":0,"iRep":0,"iSlot":-1,"iValue":0,"bUpg":false,"bOnce":false,"bStaff":false,"iWar":0,"iDaily":0,"iIndex":0,"bGuild":false,"turnin":[{"ItemID":25614,"iQty":1}],"reward":[{"ItemID":25608,"iQty":1,"iType":1,"iRate":10},{"ItemID":25611,"iQty":1,"iType":1,"iRate":10},{"ItemID":25612,"iQty":1,"iType":1,"iRate":10},{"ItemID":25613,"iQty":1,"iType":1,"iRate":10},{"ItemID":25746,"iQty":1,"iType":1,"iRate":10}],"reqd":[{"ItemID":25550,"iQty":1}],"metaValues":{},"oRewards":{"itemsR":{"0":{"ItemID":25608,"sName":"Platinum Trobble","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":20,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/pets/PlatinumTrobble.swf","sLink":"PlatinumTrobble","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Pet","sIcon":"iipet","sES":"pe","sElmt":"None","metaValues":{},"iQty":1},"1":{"ItemID":25611,"sName":"Dual Deep Terror Blades","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":20,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/swords/DeepTerrorBlade.swf","sLink":"DeepTerrorBlade","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Dagger","sIcon":"iwdagger","sES":"Weapon","sElmt":"None","metaValues":{},"iQty":1},"2":{"ItemID":25612,"sName":"Brutal Tricorn","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":20,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/helms/BrutalTricorn.swf","sLink":"BrutalTricorn","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Helm","sIcon":"iihelm","sES":"he","sElmt":"None","metaValues":{},"iQty":1},"3":{"ItemID":25613,"sName":"Ultra Silver Sun Cutlass","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":20,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/swords/GoldenCutlass4.swf","sLink":"GoldenCutlass4","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Sword","sIcon":"iwsword","sES":"Weapon","sElmt":"None","metaValues":{},"iQty":1},"4":{"ItemID":25746,"sName":"Purified Deep Terror Blade","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":20,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/swords/DeepTerrorBladeP.swf","sLink":"DeepTerrorBladeP","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Dagger","sIcon":"iwdagger","sES":"Weapon","sElmt":"None","metaValues":{},"iQty":1}}},"oItems":{"25614":{"ItemID":25614,"sName":"Dual Bonebreaker Cutlass","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":13,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sFile":"items/swords/DoomKnightNavalSword2.swf","sLink":"DoomKnightNavalSword2","iQSIndex":-1,"iQSValue":0,"sReqQuests":"3777","bHouse":false,"sType":"Dagger","sIcon":"iwdagger","sES":"Weapon","sElmt":"None","metaValues":{}}},"oReqd":{"25550":{"ItemID":25550,"sName":"Deep Terror Blade","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":30,"iCost":1000,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sFile":"items/swords/DeepTerrorBlade.swf","sLink":"DeepTerrorBlade","sMeta":"NoUndo,Undead:1.05,gold:1.05","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Sword","sIcon":"iwsword","sES":"Weapon","sElmt":"None","metaValues":{"noundo":true,"undead":1,"gold":1}}}},"5682":{"QuestID":5682,"sName":"Prove your Worth (Healer)","sDesc":"If you can prove to me that you are a rank 10 Healer and are Rank 5 Good, then I will grant you the Rank 10 Healer Medal!","sEndText":"Here you go!","iLvl":0,"iExp":0,"iGold":0,"iClass":15651,"sClass":"Healer","iReqCP":302500,"FactionID":5,"sFaction":"Good","iReqRep":22500,"iRep":0,"iSlot":-1,"iValue":0,"bUpg":false,"bOnce":false,"bStaff":false,"iWar":0,"bGuild":false,"reward":[{"ItemID":38343,"iQty":1,"iType":0,"iRate":100}],"metaValues":{},"oRewards":{"itemsS":{"0":{"ItemID":38343,"sName":"Rank 10 Healer Medal","sDesc":" ","iRng":10,"iDPS":0,"iLvl":1,"iRty":10,"iCost":0,"bTemp":false,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":true,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":{},"iQty":1}}}}}}}}
        JSON;

    #[Test]
    public function it_reads_objectives_and_rewards_of_real_quests(): void
    {
        $quests = self::quests();
        $rats = $quests[39];

        static::assertTrue(
            QuestLoadedEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"loadShop"}}}')->unwrap())->isNone(),
        );
        static::assertSame([39, 40, 342, 3295, 3777, 5682], array_keys($quests));
        static::assertSame('Missing King', $rats->name->value);
        static::assertSame(
            "Great work - now we'll never run out of rats!",
            $rats->description->unwrap()->endText->unwrap(),
        );
        static::assertEquals(
            [
                new ItemObjective(new ItemId(325), new Quantity(1)),
                new ItemObjective(new ItemId(15_198), new Quantity(1)),
            ],
            $rats->objectives,
        );
        static::assertEquals(
            [
                new GoldReward(new Gold(100)),
                new ExperienceReward(new Experience(100)),
                new ItemReward(new ItemId(333), new DropRate(100.0), new Quantity(1), ItemRewardType::Guaranteed),
            ],
            $rats->rewards,
        );

        $random = array_filter($quests[3777]->rewards, static fn($reward): bool => $reward instanceof ItemReward);
        static::assertCount(5, $random);
        foreach ($random as $reward) {
            static::assertSame(ItemRewardType::Random, $reward->type);
            static::assertSame(10.0, $reward->dropRate->percentage);
        }
    }

    #[Test]
    public function it_reads_what_is_needed_to_accept_real_quests(): void
    {
        $quests = self::quests();
        $only = static function (string $class, QuestLoadedQuest $quest): object {
            $found = array_values(array_filter(
                $quest->requirements,
                static fn(object $r): bool => $r instanceof $class,
            ));
            static::assertCount(1, $found);

            return $found[0];
        };

        static::assertSame([], $quests[39]->requirements);
        static::assertEquals([new MemberRequirement()], $quests[40]->requirements);
        static::assertSame(15, $only(PlayerLevelRequirement::class, $quests[3295])->level->value);

        $dwarfhold = $only(FactionRankRequirement::class, $quests[342]);
        static::assertSame(2, $dwarfhold->factionId->value);
        static::assertSame(4, $dwarfhold->rank->value);

        $blade = $only(ItemRequirement::class, $quests[3777]);
        static::assertSame(25_550, $blade->itemId->value);
        static::assertSame(1, $blade->quantity->value);

        $healer = $only(ClassRankRequirement::class, $quests[5682]);
        static::assertSame(15_651, $healer->classId->value);
        static::assertSame(10, $healer->rank->value);
        static::assertSame(5, $only(FactionRankRequirement::class, $quests[5682])->rank->value);

        $chained = self::load(self::quest('Armor', 'None', 0, ['bQuest' => 1, 'strPrevious' => '39']))->quests[0];
        static::assertSame(39, $only(PreviousQuestRequirement::class, $chained)->questId->value);
    }

    #[Test]
    public function it_mentions_what_the_message_says_about_items_factions_and_classes(): void
    {
        $mentions = self::load(self::QUESTS)->mentions();
        $about = static fn(int $id, string $value): array => array_values(array_map(
            static fn(Mention $mention): object => $mention->value,
            array_filter(
                $mentions,
                static fn(Mention $mention): bool => (
                    $mention->about->value === $id
                    && $mention->value instanceof $value
                ),
            ),
        ));

        static::assertEquals(
            [
                new ItemName('Deep Terror Blade'),
                Type\Weapon::Sword,
                new ItemPrice(new AdventureCoins(1000)),
                new Gamefile('items/swords/DeepTerrorBlade.swf'),
                new Linkage('DeepTerrorBlade'),
                EquipmentSlot::Weapon,
            ],
            array_values(array_map(
                static fn(Mention $mention): object => $mention->value,
                array_filter($mentions, static fn(Mention $mention): bool => $mention->about->value === 25_550),
            )),
        );
        static::assertEquals([new FactionName('Good')], $about(5, FactionName::class));
        static::assertEquals([new FactionName('Dwarfhold')], $about(2, FactionName::class));
        static::assertEquals([new ClassName('Healer')], $about(15_651, ClassName::class));
        static::assertInstanceOf(
            ClassId::class,
            array_values(array_filter(
                $mentions,
                static fn(Mention $mention): bool => $mention->value instanceof ClassName,
            ))[0]->about,
        );
        static::assertCount(1, $about(325, ItemName::class));
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
        static::assertSame($detail, self::load(self::quest($type, 'None', 0))->mentions()[1]->value);
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
        static::assertSame($slot, self::load(self::quest('Armor', $code, 0))->mentions()[2]->value);
    }

    #[Test]
    #[DataProvider('malformed')]
    public function it_rejects_what_it_does_not_know(string $message, string $reason): void
    {
        $this->expectException(MalformedEventException::class);
        $this->expectExceptionMessage($reason);

        self::load($message);
    }

    /** @return iterable<string, array{string, string}> */
    public static function malformed(): iterable
    {
        yield 'unknown item type' => [self::quest('Spaceship', 'None', 0), "unknown item type 'Spaceship'"];
        yield 'unknown slot' => [self::quest('Armor', 'xx', 0), "unknown equipment slot 'xx'"];
        yield 'unknown reward type' => [self::quest('Armor', 'None', 7), "unknown reward type '7'"];
        yield 'no quests' => ['{"b":{"o":{"cmd":"getQuests"}}}', 'quests are missing or invalid'];
    }

    /** @return array<int, QuestLoadedQuest> */
    private static function quests(): array
    {
        $quests = [];
        foreach (self::load(self::QUESTS)->quests as $quest) {
            $quests[$quest->id->value] = $quest;
        }

        return $quests;
    }

    private static function load(string $message): QuestLoadedEvent
    {
        return QuestLoadedEvent::from(JsonMessage::from($message)->unwrap())->unwrap();
    }

    /** @param array<string, mixed> $extra */
    private static function quest(string $type, string $slot, int $rewardType, array $extra = []): string
    {
        return json_encode(['b' => ['o' => [
            'cmd' => 'getQuests',
            'quests' => ['1' => [
                'QuestID' => 1,
                'sName' => 'Hero Quest',
                'sDesc' => '',
                'iLvl' => 1,
                'iExp' => 0,
                'iGold' => 0,
                'iClass' => 0,
                'iReqCP' => 0,
                'FactionID' => 1,
                'sFaction' => 'None',
                'iReqRep' => 0,
                'iRep' => 0,
                'bUpg' => false,
                'reward' => [['ItemID' => 1, 'iQty' => 1, 'iRate' => 100, 'iType' => $rewardType]],
                'oItems' => ['1' => ['ItemID' => 1, 'sName' => 'Hero Item', 'sType' => $type, 'sES' => $slot]],
                ...$extra,
            ]],
        ]]], flags: JSON_THROW_ON_ERROR);
    }
}
