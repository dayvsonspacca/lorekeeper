<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps;

use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\ItemIncrease;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class EldersBloodDailyTest extends TestCase
{
    #[Test]
    public function it_slays_fifty_gorillaphants_turns_them_in_and_picks_up_the_blood(): void
    {
        $me = Fixture::me(level: 30, map: 'battleon');
        $blood = new PlayerItem(new Resource(new ItemId(5586), new ItemName("Elders' Blood"), new ItemLevel(1)));

        [$steps, $result] = Play::run(Dailies::eldersBlood(), $me, [
            ...self::hunted(),
            [
                new QuestCompletedEvent(new QuestId(802), new QuestName("Experiment 107: Elder's Blood Potion")),
                new ItemsTurnedInEvent([new TurnedInItem(new ItemId(5574), new Quantity(50))]),
            ],
            new ItemDroppedEvent([$blood]),
            new DropPickedUpEvent(new ItemId(5586), new Quantity(1), new Quantity(1)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%arcangrove%',
                '%xt%zm%getQuests%7101%802%',
                '%xt%zm%acceptQuest%7101%802%',
                '%xt%zm%moveToCell%7101%LeftBack%Left%',
                '%xt%zm%gar%7101%0%a1>m:2%wvz%',
                '%xt%zm%tryQuestComplete%7101%802%-1%false%1%wvz%',
                '%xt%zm%getDrop%122628%5586%',
            ],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
        static::assertSame(1, $me->player()->inventory->quantity(new ItemId(5586)));
    }

    #[Test]
    public function a_reward_that_lands_straight_in_the_inventory_needs_no_pick_up(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(self::holding(10));

        [$steps, $result] = Play::run(Dailies::eldersBlood(), $me, [
            ...self::hunted(),
            [
                new QuestCompletedEvent(new QuestId(802), new QuestName("Experiment 107: Elder's Blood Potion")),
                new ItemsTurnedInEvent([new TurnedInItem(new ItemId(5574), new Quantity(50))]),
                new ItemsIncreasedEvent([new ItemIncrease(new ItemId(5586), new Quantity(1), new Quantity(11))]),
            ],
        ]);

        static::assertEquals(new Done('all steps are done'), $result);
        static::assertSame(11, $me->player()->inventory->quantity(new ItemId(5586)));
        static::assertStringNotContainsString('getDrop', implode(' ', Play::sent($steps)));
    }

    #[Test]
    public function it_stays_put_when_the_daily_was_already_done_today(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 0, 25)));

        [$steps, $result] = Play::run(Dailies::eldersBlood(), $me, [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            self::loaded(),
        ]);

        static::assertEquals(new Done('stopped: Quest 802 was already done'), $result);
        static::assertCount(2, Play::sent($steps));
    }

    #[Test]
    public function it_does_nothing_while_holding_all_the_blood_the_game_allows(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(self::holding(20));

        [$steps, $result] = Play::run(Dailies::eldersBlood(), $me, []);

        static::assertEquals(new Done('stopped: Holding item 5586 ×20'), $result);
        static::assertSame([], Play::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_server_does_not_accept_the_quest(): void
    {
        [$steps, $result] = Play::run(Dailies::eldersBlood(), Fixture::me(level: 30), [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            null,
            null,
        ]);

        static::assertEquals(new Failed('step 4 (Accept quest 802): the game would not give the quest'), $result);
        static::assertCount(3, Play::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_hunt_runs_out_of_time(): void
    {
        [, $result] = Play::run(Dailies::eldersBlood(Dailies::hourly()), Fixture::me(level: 30), [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(802)),
        ]);

        static::assertEquals(
            new Failed('step 5 (Hunt in LeftBack for item 5574 ×50): item 5574 ×50 was not held after 20 minutes'),
            $result,
        );
    }

    #[Test]
    public function it_stops_when_the_game_does_not_answer_the_turn_in(): void
    {
        [, $result] = Play::run(
            Dailies::eldersBlood(),
            Fixture::me(level: 30),
            [
                ...self::hunted(),
                null,
            ],
        );

        static::assertEquals(
            new Failed(
                'step 6 (Turn in quest 802 for item 5586): the game would not complete the quest (the game did not answer)',
            ),
            $result,
        );
    }

    /** @return list<null|Event> */
    private static function hunted(): array
    {
        return [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(802)),
            null,
            null,
            null,
            new QuestItemsReceivedEvent([
                new PlayerItem(
                    new Resource(new ItemId(5574), new ItemName('Slain Gorillaphant'), new ItemLevel(1)),
                    new Quantity(50),
                ),
            ]),
        ];
    }

    private static function holding(int $blood): InventoryLoadedEvent
    {
        return new InventoryLoadedEvent(new PlayerItems([new PlayerItem(
            new Resource(new ItemId(5586), new ItemName("Elders' Blood"), new ItemLevel(1)),
            new Quantity($blood),
        )]));
    }

    private static function loaded(): QuestLoadedEvent
    {
        return new QuestLoadedEvent([new Quest(
            new QuestId(802),
            new QuestName("Experiment 107: Elder's Blood Potion"),
            Option\none(),
            [],
            [],
            [],
            Option\some(new QuestMark(QuestPeriod::Day, 0, 25)),
        )]);
    }
}
