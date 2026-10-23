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
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\HeldItem;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\ItemIncrease;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class TokensOfCollectionDailyTest extends TestCase
{
    #[Test]
    public function it_hunts_the_crickets_for_two_tokens_turns_them_in_and_picks_up_the_reward(): void
    {
        $me = Fixture::me(level: 82, map: 'battleon');
        $token = new HeldItem(
            new Resource(new ItemId(9397), new ItemName('Token of Collection'), new ItemLevel(1), new Quantity(99)),
        );

        [$steps, $result] = Play::run(Dailies::tokensOfCollection(), $me, [
            ...self::hunted(),
            [
                new QuestCompletedEvent(new QuestId(1316), new QuestName('Tokens of Collection')),
                new ItemsTurnedInEvent([new TurnedInItem(new ItemId(9392), new Quantity(2))]),
            ],
            new ItemDroppedEvent([$token]),
            new DropPickedUpEvent(new ItemId(9397), new Quantity(1), new Quantity(2)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%Hero%terrarium%',
                '%xt%zm%getQuests%189254%1316%',
                '%xt%zm%acceptQuest%189254%1316%',
                '%xt%zm%moveToCell%189254%r2%Left%',
                '%xt%zm%gar%189254%0%a1>m:4%wvz%',
                '%xt%zm%getDrop%122628%9392%',
                '%xt%zm%moveToCell%189254%r2%Left%',
                '%xt%zm%gar%189254%0%a1>m:4%wvz%',
                '%xt%zm%tryQuestComplete%189254%1316%-1%false%1%wvz%',
                '%xt%zm%getDrop%122628%9397%',
            ],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
        static::assertSame(2, $me->hero()->inventory->quantity(new ItemId(9397)));
        static::assertSame(0, $me->hero()->inventory->quantity(new ItemId(9392)));
    }

    #[Test]
    public function it_stays_put_when_the_daily_was_already_done_today(): void
    {
        $me = Fixture::me(level: 82);
        $me->hear(new QuestMarkedEvent(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(46))));

        [$steps, $result] = Play::run(
            Dailies::tokensOfCollection(),
            $me,
            [
                self::terrarium(),
                self::loaded(),
            ],
        );

        static::assertEquals(new Done('stopped: Quest 1316 was already done'), $result);
        static::assertCount(2, Play::sent($steps));
    }

    #[Test]
    public function a_loaded_quest_other_than_the_daily_does_not_count_as_done_and_a_refused_quest_stops_it(): void
    {
        $me = Fixture::me(level: 82);
        $me->hear(new QuestMarkedEvent(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(25))));

        [$steps, $result] = Play::run(Dailies::tokensOfCollection(), $me, [
            self::terrarium(),
            new QuestLoadedEvent([new Quest(
                new QuestId(802),
                new QuestName("Experiment 107: Elder's Blood Potion"),
                [],
                [],
                [],
                Option\none(),
                Option\some(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(25))),
            )]),
            null,
        ]);

        static::assertEquals(new Failed('step 3 (Accept quest 1316): the game would not give the quest'), $result);
        static::assertCount(3, Play::sent($steps));
    }

    #[Test]
    public function it_stops_when_no_carnivorous_cricket_stands_in_r2(): void
    {
        [, $result] = Play::run(Dailies::tokensOfCollection(), Fixture::me(level: 82), [
            Fixture::arrivalOf('terrarium', 189_254, [Fixture::spawn(3, 'r2', Fixture::kind(13, 'Death on Wings'))]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(1316)),
        ]);

        static::assertEquals(
            new Failed(
                'step 4 (Hunt Carnivorous Cricket in r2 for item 9392 ×2): There is no Carnivorous Cricket in r2.',
            ),
            $result,
        );
    }

    #[Test]
    public function it_stops_when_the_hunt_runs_out_of_time(): void
    {
        [, $result] = Play::run(Dailies::tokensOfCollection(Dailies::hourly()), Fixture::me(level: 82), [
            self::terrarium(),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(1316)),
        ]);

        static::assertEquals(
            new Failed(
                'step 4 (Hunt Carnivorous Cricket in r2 for item 9392 ×2): item 9392 ×2 was not held after 10 minutes',
            ),
            $result,
        );
    }

    #[Test]
    public function it_stops_when_the_token_never_drops(): void
    {
        [, $result] = Play::run(Dailies::tokensOfCollection(), Fixture::me(level: 82), [
            ...self::hunted(),
            new QuestCompletedEvent(new QuestId(1316), new QuestName('Tokens of Collection')),
            null,
        ]);

        static::assertEquals(
            new Failed(
                'step 5 (Turn in quest 1316 for item 9397): the quest was completed, but item 9397 could not be picked up',
            ),
            $result,
        );
    }

    /** @return list<null|Event> */
    private static function hunted(): array
    {
        return [
            self::terrarium(),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(1316)),
            null,
            null,
            null,
            new ItemDroppedEvent([new HeldItem(
                new Resource(
                    new ItemId(9392),
                    new ItemName('This Might Be A Token'),
                    new ItemLevel(1),
                    new Quantity(99),
                ),
            )]),
            new DropPickedUpEvent(new ItemId(9392), new Quantity(1), new Quantity(1)),
            null,
            null,
            null,
            new ItemsIncreasedEvent([new ItemIncrease(new ItemId(9392), new Quantity(1), new Quantity(2))]),
        ];
    }

    private static function terrarium(): MovedToMapEvent
    {
        return Fixture::arrivalOf('terrarium', 189_254, [
            Fixture::spawn(3, 'r2', Fixture::kind(13, 'Death on Wings')),
            Fixture::spawn(4, 'r2', Fixture::kind(17, 'Carnivorous Cricket')),
            Fixture::spawn(5, 'r2', Fixture::kind(28, 'Killer Cricket')),
        ]);
    }

    private static function loaded(): QuestLoadedEvent
    {
        return new QuestLoadedEvent([new Quest(
            new QuestId(1316),
            new QuestName('Tokens of Collection'),
            [],
            [],
            [],
            Option\none(),
            Option\some(new QuestGroupId(QuestGroup::Daily, new QuestGroupNumber(46))),
        )]);
    }
}
