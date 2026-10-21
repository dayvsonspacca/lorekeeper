<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\CryptoTokenScript;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
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
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsGainedEvent;
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
use RuntimeException;

final class CryptoTokenScriptTest extends TestCase
{
    #[Test]
    public function it_accepts_hunts_the_ore_turns_it_in_and_picks_up_the_token(): void
    {
        $me = Fixture::me(level: 4, map: 'battleon');
        $token = new PlayerItem(new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1)));

        [$steps, $result] = Play::run(new CryptoTokenScript(), $me, [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new TemporaryItemsGainedEvent([new PlayerItem(
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
            self::sent($steps),
        );
        static::assertEquals(new Done("got today's Crypto Token, 1 held now"), $result);
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

        [$steps] = Play::run(new CryptoTokenScript(), $me, [
            null,
            null,
            new PlayerStateUpdatedEvent(new PlayerName('lorekeeper03'), CombatState::Idle),
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            null,
        ]);

        static::assertSame(
            ['%xt%zm%moveToCell%1%Enter%Spawn%', '%xt%zm%cmd%1%tfer%lorekeeper03%boxes%'],
            array_slice(self::sent($steps), offset: 0, length: 2),
        );
    }

    #[Test]
    public function it_stays_put_when_the_daily_was_already_done_today(): void
    {
        $me = Fixture::me(level: 4);
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 2, 6)));

        [$steps, $result] = Play::run(new CryptoTokenScript(), $me, [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
        ]);

        static::assertEquals(new Done('Crypto Token was already taken today'), $result);
        static::assertSame(
            ['%xt%zm%cmd%1%tfer%lorekeeper03%boxes%', '%xt%zm%getQuests%138097%6187%'],
            self::sent($steps),
        );
    }

    #[Test]
    public function it_stops_when_the_server_does_not_accept_the_quest(): void
    {
        [$steps, $result] = Play::run(new CryptoTokenScript(), Fixture::me(level: 4), [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            null,
        ]);

        static::assertEquals(new Failed("the game would not give the quest Odd's 'n' Ends"), $result);
        static::assertCount(3, self::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_reward_never_drops(): void
    {
        $me = Fixture::me(level: 4);

        [, $result] = Play::run(new CryptoTokenScript(), $me, [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new TemporaryItemsGainedEvent([new PlayerItem(
                new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)),
            )]),
            new QuestCompletedEvent(new QuestId(6187), new QuestName("Odd's 'n' Ends")),
            null,
        ]);

        static::assertEquals(
            new Failed("Odd's 'n' Ends was completed, but the Crypto Token could not be picked up"),
            $result,
        );
    }

    #[Test]
    public function it_stops_with_the_reason_when_the_game_will_not_complete_the_quest(): void
    {
        [$steps, $result] = Play::run(new CryptoTokenScript(), Fixture::me(level: 4), [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new TemporaryItemsGainedEvent([new PlayerItem(
                new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)),
            )]),
            new QuestTurnInRefusedEvent(new QuestId(6187), new QuestName("Odd's 'n' Ends"), 'Missing Badge'),
        ]);

        static::assertEquals(
            new Failed("the game would not complete Odd's 'n' Ends: it was already done today"),
            $result,
        );
        static::assertStringNotContainsString('getDrop', implode(' ', self::sent($steps)));
    }

    #[Test]
    public function it_does_not_travel_when_already_in_boxes(): void
    {
        $me = Fixture::me(level: 4, map: 'boxes');
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 2, 6)));

        [$steps, $result] = Play::run(new CryptoTokenScript(), $me, [self::loaded()]);

        static::assertEquals(new Done('Crypto Token was already taken today'), $result);
        static::assertSame(['%xt%zm%getQuests%1%6187%'], self::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_server_never_answers_the_join(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not join boxes: no answer');

        Play::run(new CryptoTokenScript(), Fixture::me(level: 4), [
            new TimedOut(new Await([MovedToMapEvent::class], Duration::seconds(15))),
        ]);
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
