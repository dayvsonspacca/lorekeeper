<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Quest;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\DropRate;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\Reputation;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;
use Lorekeeper\Protocol\Event\Quest\QuestFacts;
use Lorekeeper\Protocol\Event\Quest\QuestRewardFacts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QuestRewardFactsTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, list<object>}> */
    public static function quests(): iterable
    {
        yield 'no rewards' => [[], []];
        yield 'gold and experience' => [
            ['iGold' => 1500, 'iExp' => 100],
            [new GoldReward(new Gold(1500)), new ExperienceReward(new Experience(100))],
        ];
        yield 'reputation' => [
            ['FactionID' => 5, 'sFaction' => 'Good', 'iRep' => 500],
            [new ReputationReward(new FactionId(5), new Reputation(500))],
        ];
        yield 'reputation without faction' => [['iRep' => 500], []];
        yield 'items' => [
            ['reward' => [
                ['ItemID' => 100, 'iQty' => 1, 'iRate' => 100],
                ['ItemID' => 7, 'iQty' => 2, 'iRate' => 12.5],
            ]],
            [
                new ItemReward(new ItemId(100), new DropRate(100.0), 1),
                new ItemReward(new ItemId(7), new DropRate(12.5), 2),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $quest
     * @param list<object> $rewards
     */
    #[Test]
    #[DataProvider('quests')]
    public function it_extracts_rewards(array $quest, array $rewards): void
    {
        $facts = QuestRewardFacts::of(new QuestId(3), QuestFacts::type()->coerce(QuestStub::quest($quest)));

        static::assertEquals($rewards, array_map(static fn($fact): object => $fact->value, $facts));
    }
}
