<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Unit;

use InvalidArgumentException;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\Position;
use Lorekeeper\Apps\Wiki\Bots\Scripts\Done;
use Lorekeeper\Apps\Wiki\Bots\Scripts\OpenShopsScript;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Server\RoomId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class BotScriptsTest extends TestCase
{
    #[Test]
    public function going_to_the_map_the_bot_is_in_does_nothing(): void
    {
        $steps = BotScript::GoToMap->script(self::hero(), self::battleon(), ['map' => 'battleon'])->run();

        static::assertFalse($steps->valid());
        static::assertEquals(new Done('Already in battleon'), $steps->getReturn());
    }

    #[Test]
    public function going_to_another_map_asks_for_it_and_reports_refusals(): void
    {
        $moved = BotScript::GoToMap->script(self::hero(), self::battleon(), ['map' => 'forest'])->run();
        static::assertSame('%xt%zm%cmd%1%tfer%Hero%forest%', $moved->current()->command->toMessage());
        $moved->send(
            new MovedToMapEvent(new AreaId(7), new RoomId(1), new MapName('forest'), new Gamefile('f.swf'), [], [], []),
        );
        static::assertEquals(new Done('Now in forest'), $moved->getReturn());

        $locked = BotScript::GoToMap->script(self::hero(), self::battleon(), ['map' => 'forest'])->run();
        $locked->current();
        $refusal = new MapLockedEvent();
        $locked->send($refusal);
        static::assertSame($refusal, $locked->getReturn());
    }

    #[Test]
    public function asking_for_quests_uses_the_current_area_and_counts_what_came_back(): void
    {
        $steps = BotScript::AskQuests->script(self::hero(), self::battleon(), [
            'map' => 'battleon',
            'quests' => ['3', '5'],
        ])->run();

        static::assertSame('%xt%zm%getQuests%1350%3%5%', $steps->current()->command->toMessage());
        $steps->send(new QuestLoadedEvent([], []));

        static::assertEquals(new Done('Loaded 0 of 2 quests'), $steps->getReturn());
    }

    #[Test]
    public function opening_shops_pauses_between_them_and_counts_the_ones_that_opened(): void
    {
        $steps = new OpenShopsScript(self::hero(), self::battleon(), new MapName('battleon'), [
            new ShopId(31),
            new ShopId(609),
        ])->run();

        static::assertSame('%xt%zm%loadShop%1350%31%', $steps->current()->command->toMessage());
        $steps->send(new ShopLoadedEvent(new ShopId(31), new ShopName('Battleon Assorted shop'), false, [], []));
        static::assertEquals(new Pause(Duration::seconds(2)), $steps->current());
        $steps->send(null);
        static::assertSame('%xt%zm%loadShop%1350%609%', $steps->current()->command->toMessage());
        $steps->send(new TimedOut(new Await([ShopLoadedEvent::class], Duration::seconds(15))));

        static::assertEquals(new Done('Opened 1 of 2 shops'), $steps->getReturn());
    }

    #[Test]
    public function each_script_summarizes_its_parameters(): void
    {
        static::assertSame('forest', BotScript::GoToMap->summary(['map' => 'forest']));
        static::assertSame('battleon · 2 quests', BotScript::AskQuests->summary([
            'map' => 'battleon',
            'quests' => ['3', '5'],
        ]));
        static::assertSame('— · 0 shops', BotScript::OpenShops->summary([]));
        static::assertSame(['map' => null, 'quests' => null, 'shops' => null], BotScript::blankParameters());
    }

    #[Test]
    public function a_task_without_ids_cannot_be_played(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BotScript::OpenShops->script(self::hero(), self::battleon(), ['map' => 'battleon', 'shops' => ['x']]);
    }

    private static function hero(): PlayerName
    {
        return new PlayerName('Hero');
    }

    private static function battleon(): Position
    {
        return new Position(new MapName('battleon'), new AreaId(1350));
    }
}
