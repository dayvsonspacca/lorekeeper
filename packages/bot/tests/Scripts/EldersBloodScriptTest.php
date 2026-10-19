<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\EldersBloodScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Item\ItemId;
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
use Lorekeeper\Server\Event\Inventory\AddedItem;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\MoreItemsAddedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\QuestCompletedEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Quest\QuestMarkedEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Option;

final class EldersBloodScriptTest extends TestCase
{
    #[Test]
    public function it_slays_fifty_gorillaphants_turns_them_in_and_picks_up_the_blood(): void
    {
        $me = Fixture::me(level: 30, map: 'battleon');
        $blood = new PlayerItem(new Resource(new ItemId(5586), new ItemName("Elders' Blood")));

        [$steps, $result] = Play::run(new EldersBloodScript(), $me, [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(802)),
            null,
            null,
            null,
            new TemporaryItemsAddedEvent([
                new PlayerItem(new Resource(new ItemId(5574), new ItemName('Slain Gorillaphant')), new Quantity(50)),
            ]),
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
            self::sent($steps),
        );
        static::assertEquals(new Done("got today's Elders' Blood, 1 held now"), $result);
    }

    #[Test]
    public function a_reward_that_lands_straight_in_the_inventory_needs_no_pick_up(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(new PlayerInventoryLoadedEvent(new PlayerItems([new PlayerItem(
            new Resource(new ItemId(5586), new ItemName("Elders' Blood")),
            new Quantity(10),
        )])));

        [$steps, $result] = Play::run(new EldersBloodScript(), $me, [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(802)),
            null,
            null,
            null,
            new TemporaryItemsAddedEvent([
                new PlayerItem(new Resource(new ItemId(5574), new ItemName('Slain Gorillaphant')), new Quantity(50)),
            ]),
            [
                new QuestCompletedEvent(new QuestId(802), new QuestName("Experiment 107: Elder's Blood Potion")),
                new ItemsTurnedInEvent([new TurnedInItem(new ItemId(5574), new Quantity(50))]),
                new MoreItemsAddedEvent([new AddedItem(new ItemId(5586), new Quantity(1), new Quantity(11))]),
            ],
        ]);

        static::assertEquals(new Done("got today's Elders' Blood, 11 held now"), $result);
        static::assertStringNotContainsString('getDrop', implode(' ', self::sent($steps)));
    }

    #[Test]
    public function it_stays_put_when_the_daily_was_already_done_today(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 0, 25)));

        [$steps, $result] = Play::run(new EldersBloodScript(), $me, [
            Fixture::arrival('arcangrove', 7_101, ['LeftBack' => [2, 30]]),
            self::loaded(),
        ]);

        static::assertEquals(new Done("Elders' Blood was already taken today"), $result);
        static::assertCount(2, self::sent($steps));
    }

    #[Test]
    public function it_does_nothing_while_holding_all_the_blood_the_game_allows(): void
    {
        $me = Fixture::me(level: 30);
        $me->hear(new PlayerInventoryLoadedEvent(new PlayerItems([new PlayerItem(
            new Resource(new ItemId(5586), new ItemName("Elders' Blood")),
            new Quantity(20),
        )])));

        [$steps, $result] = Play::run(new EldersBloodScript(), $me, []);

        static::assertEquals(new Done("already holding the 20 Elders' Blood the game allows"), $result);
        static::assertSame([], self::sent($steps));
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

    /**
     * @param list<Instruction> $steps
     *
     * @return list<string>
     */
    private static function sent(array $steps): array
    {
        return array_values(array_map(
            static fn(Send $send): string => $send->command->toMessage(),
            array_filter($steps, static fn(Instruction $step): bool => $step instanceof Send),
        ));
    }
}
