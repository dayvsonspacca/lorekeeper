<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Quest;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\DropRate;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemType;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Faction\Reputation;
use Lorekeeper\Core\World\Quest\QuestDescription;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;
use Lorekeeper\Core\World\Quest\TurnInItem;
use Lorekeeper\Protocol\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestLoadedEventTest extends TestCase
{
    private const string SPECIALTY_PIZZA = '{"t":"xt","b":{"r":-1,"o":{"cmd":"getQuests","quests":{"14":{"QuestID":14,"sName":"Specialty Pizza","sDesc":"I have an urgent delivery! ... and our policy is 30 levels o","sEndText":"Ooh, thanks! I know some people say that they\'d like original pizzas again, but I just LOVE these.","iLvl":1,"iExp":1500,"iGold":1500,"iClass":0,"iReqCP":0,"FactionID":5,"sFaction":"Good","iReqRep":0,"iRep":500,"iSlot":-1,"iValue":0,"bUpg":false,"bOnce":false,"bStaff":false,"iWar":0,"bGuild":false,"turnin":[{"ItemID":98,"iQty":5}],"reward":[{"ItemID":100,"iQty":1,"iType":0,"iRate":100}],"metaValues":[],"oRewards":{"itemsS":[{"ItemID":100,"sName":"Special Hair Gel","sDesc":" ","iRng":10,"iDPS":100,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":1,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"","bHouse":false,"sType":"Resource","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":[],"iQty":1}]},"oItems":{"98":{"ItemID":98,"sName":"Yummy Slime Sauce","sDesc":" ","iRng":10,"iDPS":100,"iLvl":1,"iRty":10,"iCost":0,"bTemp":true,"iStk":5,"bPTR":false,"bUpg":false,"bCoins":false,"bStaff":false,"sLink":"","iQSIndex":-1,"iQSValue":0,"sReqQuests":"14","bHouse":false,"sType":"Quest Item","sIcon":"iibag","sES":"None","sElmt":"None","metaValues":[]}}}}}}}';

    #[Test]
    public function it_extracts_facts_from_a_real_quest(): void
    {
        $event = QuestLoadedEvent::from(JsonMessage::from(self::SPECIALTY_PIZZA)->unwrap())->unwrap();

        $quest = new QuestId(14);
        $good = new FactionId(5);

        static::assertEquals(
            [
                new Fact($quest, new QuestName('Specialty Pizza')),
                new Fact(
                    $quest,
                    new QuestDescription(
                        'I have an urgent delivery! ... and our policy is 30 levels o',
                        "Ooh, thanks! I know some people say that they'd like original pizzas again, but I just LOVE these.",
                    ),
                ),
                new Fact($good, new FactionName('Good')),
                new Fact($quest, new TurnInItem(new ItemId(98), 5)),
                new Fact($quest, new GoldReward(new Gold(1500))),
                new Fact($quest, new ExperienceReward(new Experience(1500))),
                new Fact($quest, new ReputationReward($good, new Reputation(500))),
                new Fact($quest, new ItemReward(new ItemId(100), new DropRate(100.0), 1)),
                new Fact(new ItemId(98), new ItemName('Yummy Slime Sauce')),
                new Fact(new ItemId(98), new ItemType('Quest Item')),
                new Fact(new ItemId(100), new ItemName('Special Hair Gel')),
                new Fact(new ItemId(100), new ItemType('Resource')),
            ],
            $event->facts,
        );
    }

    #[Test]
    public function it_handles_an_empty_quest_list(): void
    {
        $message = JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"getQuests","quests":{}}}}')->unwrap();

        static::assertSame([], QuestLoadedEvent::from($message)->unwrap()->facts);
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        $message = JsonMessage::from('{"b":{"o":{"cmd":"moveToArea"}}}')->unwrap();

        static::assertTrue(QuestLoadedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_ignores_delimited_message(): void
    {
        $message = DelimitedMessage::from('%xt%server%-1%Profanity filter On.%')->unwrap();

        static::assertTrue(QuestLoadedEvent::from($message)->isNone());
    }

    #[Test]
    public function it_rejects_missing_quests(): void
    {
        $this->expectException(MalformedEventException::class);

        QuestLoadedEvent::from(new JsonMessage(['cmd' => 'getQuests'], ''));
    }

    #[Test]
    public function it_rejects_quest_without_name(): void
    {
        $this->expectException(MalformedEventException::class);

        QuestLoadedEvent::from(new JsonMessage([
            'cmd' => 'getQuests',
            'quests' => [3 => QuestStub::quest(['sName' => ''])],
        ], ''));
    }
}
