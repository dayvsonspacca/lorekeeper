<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Script;

use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Task\AskQuestsTask;
use Lorekeeper\Client\Bot\Task\GoToMapTask;
use Lorekeeper\Client\Bot\Task\OpenShopsTask;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Client\Tests\Script\Game\Replay;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class BotScriptsTest extends TestCase
{
    #[Test]
    public function going_to_the_map_the_bot_is_in_does_nothing(): void
    {
        [$steps, $result] = Replay::run(self::goTo('battleon'));

        static::assertSame([], $steps);
        static::assertEquals(new Done('Already in battleon'), $result);
    }

    #[Test]
    public function going_to_another_map_asks_for_it_and_reports_refusals(): void
    {
        [$steps, $result] = Replay::run(
            self::goTo('forest'),
            new MovedToMapEvent(new AreaId(7), new RoomId(1), new MapName('forest'), new Gamefile('f.swf'), [], [], []),
        );
        static::assertInstanceOf(Send::class, $steps[0]);
        static::assertSame('%xt%zm%cmd%1%tfer%Hero%forest%', $steps[0]->command->toMessage());
        static::assertEquals(new Done('Now in forest'), $result);

        $refusal = new MapLockedEvent();
        static::assertSame($refusal, Replay::run(self::goTo('forest'), $refusal)[1]);
    }

    #[Test]
    public function asking_for_quests_uses_the_current_area_and_tells_how_many_batches_are_done(): void
    {
        $quests = array_map(static fn(int $id): QuestId => new QuestId($id), range(start: 1, end: 12));
        $task = new AskQuestsTask(new MapName('battleon'), $quests);

        [$steps, $result] = Replay::run(
            $task->script(self::me()),
            null,
            new QuestLoadedEvent([], []),
            null,
            null,
            new QuestLoadedEvent([], []),
        );

        static::assertEquals(new Progress(0, 2), $steps[0]);
        static::assertInstanceOf(Send::class, $steps[1]);
        static::assertSame('%xt%zm%getQuests%1350%1%2%3%4%5%6%7%8%9%10%', $steps[1]->command->toMessage());
        static::assertEquals(new Progress(1, 2), $steps[2]);
        static::assertEquals(new Pause(Duration::seconds(2)), $steps[3]);
        static::assertInstanceOf(Send::class, $steps[4]);
        static::assertSame('%xt%zm%getQuests%1350%11%12%', $steps[4]->command->toMessage());
        static::assertEquals(new Progress(2, 2), $steps[5]);
        static::assertEquals(new Done('Loaded 0 of 12 quests'), $result);
        static::assertSame('Ask for 12 quests in battleon', $task->title());
    }

    #[Test]
    public function opening_shops_pauses_between_them_and_counts_the_ones_that_opened(): void
    {
        $task = new OpenShopsTask(new MapName('battleon'), [new ShopId(31), new ShopId(609)]);

        [$steps, $result] = Replay::run(
            $task->script(self::me()),
            null,
            new ShopLoadedEvent(new ShopId(31), new ShopName('Battleon Assorted shop'), false, [], []),
            null,
            null,
            new TimedOut(new Await([ShopLoadedEvent::class], Duration::seconds(15))),
        );

        static::assertEquals(new Progress(0, 2), $steps[0]);
        static::assertInstanceOf(Send::class, $steps[1]);
        static::assertSame('%xt%zm%loadShop%1350%31%', $steps[1]->command->toMessage());
        static::assertEquals(new Progress(1, 2), $steps[2]);
        static::assertEquals(new Pause(Duration::seconds(2)), $steps[3]);
        static::assertInstanceOf(Send::class, $steps[4]);
        static::assertSame('%xt%zm%loadShop%1350%609%', $steps[4]->command->toMessage());
        static::assertEquals(new Progress(2, 2), $steps[5]);
        static::assertEquals(new Done('Opened 1 of 2 shops'), $result);
        static::assertSame('Open 2 shops in battleon', $task->title());
        static::assertSame(0.5, $steps[2]->ratio());
    }

    /** @return Script<object> */
    private static function goTo(string $map): Script
    {
        return new GoToMapTask(new MapName($map))->script(self::me());
    }

    private static function me(): Character
    {
        $me = new Character(new PlayerName('Hero'));
        $me->entered(
            new InGame(
                new SocketId(1),
                new MovedToMapEvent(
                    new AreaId(1350),
                    new RoomId(1),
                    new MapName('battleon'),
                    new Gamefile('b.swf'),
                    [],
                    [],
                    [],
                ),
            ),
        );

        return $me;
    }
}
