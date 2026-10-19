<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\CryptoTokenScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Tests\Fixture;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\QuestAcceptedEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CryptoTokenScriptTest extends TestCase
{
    #[Test]
    public function it_accepts_hunts_the_ore_turns_it_in_and_picks_up_the_token(): void
    {
        $me = Fixture::me(level: 4, map: 'battleon');
        $token = new PlayerItem(new Resource(new ItemId(42_657), new ItemName('Crypto Token')));

        [$steps, $result] = Play::run(new CryptoTokenScript(), $me, [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new TemporaryItemsAddedEvent([new PlayerItem(new Resource(new ItemId(42_658), new ItemName('Metal Ore')))]),
            new ItemsTurnedInEvent([new TurnedInItem(new ItemId(42_658), new Quantity(1))]),
            new ItemDroppedEvent([$token]),
            new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(1)),
        ]);

        static::assertSame(
            [
                '%xt%zm%cmd%1%tfer%lorekeeper03%boxes%',
                '%xt%zm%acceptQuest%138097%6187%',
                '%xt%zm%moveToCell%138097%Closet%Center%',
                '%xt%zm%gar%138097%0%a1>m:5%wvz%',
                '%xt%zm%tryQuestComplete%138097%6187%-1%false%1%wvz%',
                '%xt%zm%getDrop%122628%42657%',
            ],
            self::sent($steps),
        );
        static::assertEquals(new Done('Got a Crypto Token, now holding 1'), $result);
        static::assertSame(0, $me->player()->inventory->quantity(new ItemId(42_658)));
    }

    #[Test]
    public function it_stops_when_the_server_does_not_accept_the_quest(): void
    {
        [$steps, $result] = Play::run(new CryptoTokenScript(), Fixture::me(level: 4), [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            null,
        ]);

        static::assertEquals(new Failed('the server did not accept the Crypto Token quest'), $result);
        static::assertCount(2, self::sent($steps));
    }

    #[Test]
    public function it_stops_when_the_reward_never_drops(): void
    {
        $me = Fixture::me(level: 4);

        [, $result] = Play::run(new CryptoTokenScript(), $me, [
            Fixture::arrival('boxes', 138_097, ['Closet' => [5, 1]]),
            new QuestAcceptedEvent(new QuestId(6187)),
            null,
            null,
            null,
            new TemporaryItemsAddedEvent([new PlayerItem(new Resource(new ItemId(42_658), new ItemName('Metal Ore')))]),
            new ItemsTurnedInEvent([new TurnedInItem(new ItemId(42_658), new Quantity(1))]),
            null,
        ]);

        static::assertEquals(new Failed('the quest was turned in but the Crypto Token was never picked up'), $result);
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
