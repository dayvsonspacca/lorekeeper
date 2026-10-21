<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Steps;

use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\Quest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Player\PlayerStateUpdatedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Event\Quest\QuestTurnInRefusedEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class CryptoTokenDailyTest extends TestCase
{
    #[Test]
    public function it_accepts_hunts_the_ore_turns_it_in_and_picks_up_the_token(): void
    {
        $me = Fixture::me(level: 4, map: 'battleon');
        $token = new PlayerItem(new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1)));

        [$steps, $result] = Play::run(Dailies::cryptoToken(), $me, [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new QuestItemsReceivedEvent([new PlayerItem(
                new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)),
            )]),
            [
                new QuestCompletedEvent(new QuestId(6187), new QuestName("Odd's 'n' Ends")),
                new ItemsTurnedInEvent([new TurnedInItem(new ItemId(42_658), new Quantity(1))]),
            ],
            new ItemDroppedEvent([$token]),
            new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(1)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%boxes%',
                '%xt%zm%getQuests%138097%6187%',
                '%xt%zm%acceptQuest%138097%6187%',
                '%xt%zm%moveToCell%138097%Closet%Center%',
                '%xt%zm%gar%138097%0%a1>m:5%wvz%',
                '%xt%zm%tryQuestComplete%138097%6187%-1%false%1%wvz%',
                '%xt%zm%getDrop%122628%42657%',
            ],
            Play::sent($steps),
        );
        static::assertEquals(new Done('all steps are done'), $result);
        static::assertSame(1, $me->player()->inventory->quantity(new ItemId(42_657)));
        static::assertSame(0, $me->player()->inventory->quantity(new ItemId(42_658)));
    }

    #[Test]
    public function it_leaves_the_fight_a_task_before_left_behind_before_travelling(): void
    {
        $me = Fixture::me(level: 4, map: 'arcangrove');
        $me->hear(new PlayersStateChangedEvent([new PlayerState(
            new PlayerName('lorekeeper03'),
            CombatState::Fighting,
        )]));

        [$steps] = Play::run(Dailies::cryptoToken(), $me, [
            null,
            null,
            new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Idle),
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            null,
        ]);

        static::assertSame(
            ['%xt%zm%moveToCell%1%Enter%Spawn%', '%xt%zm%cmd%1%tfer%lorekeeper03%boxes%'],
            array_slice(Play::sent($steps), offset: 0, length: 2),
        );
    }

    #[Test]
    public function it_stays_put_when_the_daily_was_already_done_today(): void
    {
        $me = Fixture::me(level: 4);
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 2, 6)));

        [$steps, $result] = Play::run(Dailies::cryptoToken(), $me, [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
        ]);

        static::assertEquals(new Done('stopped: Quest 6187 was already done'), $result);
        static::assertSame(
            ['%xt%zm%cmd%1%tfer%lorekeeper03%boxes%', '%xt%zm%getQuests%138097%6187%'],
            Play::sent($steps),
        );
    }

    #[Test]
    public function it_stops_when_the_server_does_not_accept_the_quest(): void
    {
        [$steps, $result] = Play::run(Dailies::cryptoToken(), Fixture::me(level: 4), [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            null,
        ]);

        static::assertEquals(new Failed('step 3 (Accept quest 6187): the game would not give the quest'), $result);
        static::assertCount(3, Play::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_reward_never_drops(): void
    {
        [, $result] = Play::run(Dailies::cryptoToken(), Fixture::me(level: 4), [
            ...self::hunted(),
            new QuestCompletedEvent(new QuestId(6187), new QuestName("Odd's 'n' Ends")),
            null,
        ]);

        static::assertEquals(
            new Failed(
                'step 5 (Turn in quest 6187 for item 42657): the quest was completed, but item 42657 could not be picked up',
            ),
            $result,
        );
    }

    #[Test]
    public function it_stops_with_the_reason_when_the_game_will_not_complete_the_quest(): void
    {
        [$steps, $result] = Play::run(Dailies::cryptoToken(), Fixture::me(level: 4), [
            ...self::hunted(),
            new QuestTurnInRefusedEvent(new QuestId(6187), new QuestName("Odd's 'n' Ends"), 'Missing Badge'),
        ]);

        static::assertEquals(
            new Failed(
                'step 5 (Turn in quest 6187 for item 42657): the game would not complete the quest (Missing Badge)',
            ),
            $result,
        );
        static::assertStringNotContainsString('getDrop', implode(' ', Play::sent($steps)));
    }

    #[Test]
    public function it_does_not_travel_when_already_in_boxes(): void
    {
        $me = Fixture::me(level: 4, map: 'boxes');
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 2, 6)));

        [$steps, $result] = Play::run(Dailies::cryptoToken(), $me, [self::loaded()]);

        static::assertEquals(new Done('stopped: Quest 6187 was already done'), $result);
        static::assertSame(['%xt%zm%getQuests%1%6187%'], Play::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_server_never_answers_the_join(): void
    {
        [, $result] = Play::run(Dailies::cryptoToken(), Fixture::me(level: 4), [
            new TimedOut(new Await([MovedToMapEvent::class], Duration::seconds(15))),
        ]);

        static::assertEquals(new Failed('step 1 (Join boxes): Could not join boxes: no answer'), $result);
    }

    /** @return list<null|Event> */
    private static function hunted(): array
    {
        return [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new QuestItemsReceivedEvent([new PlayerItem(
                new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)),
            )]),
        ];
    }

    private static function loaded(): QuestLoadedEvent
    {
        return new QuestLoadedEvent([new Quest(
            new QuestId(6187),
            new QuestName("Odd's 'n' Ends"),
            Option\none(),
            [],
            [],
            [],
            Option\some(new QuestMark(QuestPeriod::Day, 2, 6)),
        )]);
    }
}
