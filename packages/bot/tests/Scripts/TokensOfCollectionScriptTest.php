<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\TokensOfCollectionScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
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

final class TokensOfCollectionScriptTest extends TestCase
{
    #[Test]
    public function it_hunts_the_crickets_for_two_tokens_turns_them_in_and_picks_up_the_reward(): void
    {
        $me = Fixture::me(level: 82, map: 'battleon');
        $found = new PlayerItem(
            new Resource(new ItemId(9392), new ItemName('This Might Be A Token'), new ItemLevel(1)),
        );
        $token = new PlayerItem(new Resource(new ItemId(9397), new ItemName('Token of Collection'), new ItemLevel(1)));

        [$steps, $result] = Play::run(new TokensOfCollectionScript(), $me, [
            self::terrarium(),
            self::loaded(),
            new QuestAcceptedEvent(new QuestId(1316)),
            null,
            null,
            null,
            new ItemDroppedEvent([$found]),
            new DropPickedUpEvent(new ItemId(9392), new Quantity(1), new Quantity(1)),
            null,
            null,
            null,
            new MoreItemsAddedEvent([new AddedItem(new ItemId(9392), new Quantity(1), new Quantity(2))]),
            [
                new QuestCompletedEvent(new QuestId(1316), new QuestName('Tokens of Collection')),
                new ItemsTurnedInEvent([new TurnedInItem(new ItemId(9392), new Quantity(2))]),
            ],
            new ItemDroppedEvent([$token]),
            new DropPickedUpEvent(new ItemId(9397), new Quantity(1), new Quantity(2)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%terrarium%',
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
            self::sent($steps),
        );
        static::assertEquals(new Done("got today's Token of Collection, 2 held now"), $result);
        static::assertSame(0, $me->player()->inventory->quantity(new ItemId(9392)));
    }

    #[Test]
    public function it_stays_put_when_the_daily_was_already_done_today(): void
    {
        $me = Fixture::me(level: 82);
        $me->hear(new QuestMarkedEvent(new QuestMark(QuestPeriod::Day, 1, 14)));

        [$steps, $result] = Play::run(new TokensOfCollectionScript(), $me, [self::terrarium(), self::loaded()]);

        static::assertEquals(new Done('Tokens of Collection was already taken today'), $result);
        static::assertCount(2, self::sent($steps));
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
            Option\none(),
            [],
            [],
            [],
            Option\some(new QuestMark(QuestPeriod::Day, 1, 14)),
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
