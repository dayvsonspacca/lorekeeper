<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Unit;

use Generator;
use InvalidArgumentException;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\Scripts\AskQuestsScript;
use Lorekeeper\Apps\Wiki\Bots\Scripts\Done;
use Lorekeeper\Apps\Wiki\Bots\Scripts\OpenShopsScript;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\LoginSucceededEvent;
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
    public function asking_for_quests_ends_with_how_many_came_back(): void
    {
        $steps = self::entered(BotScript::AskQuests->script(self::login(), [
            'map' => 'battleon',
            'quests' => ['3', '5'],
        ]));

        static::assertInstanceOf(Send::class, $steps->current());
        static::assertSame('%xt%zm%getQuests%1350%3%5%', $steps->current()->command->toMessage());
        $steps->send(new QuestLoadedEvent([], []));

        static::assertEquals(new Done('Loaded 0 of 2 quests'), $steps->getReturn());
    }

    #[Test]
    public function opening_shops_pauses_between_them_and_counts_the_ones_that_opened(): void
    {
        $steps = self::entered(new OpenShopsScript(self::login(), new MapName('battleon'), [
            new ShopId(31),
            new ShopId(609),
        ]));

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
        static::assertSame('forest', BotScript::StayInMap->summary(['map' => 'forest']));
        static::assertSame('battleon · 2 quests', BotScript::AskQuests->summary([
            'map' => 'battleon',
            'quests' => ['3', '5'],
        ]));
        static::assertSame('— · 0 shops', BotScript::OpenShops->summary([]));
        static::assertSame(['map' => null, 'quests' => null, 'shops' => null], BotScript::blankParameters());
        static::assertInstanceOf(AskQuestsScript::class, BotScript::AskQuests->script(self::login(), ['quests' => [
            7,
        ]]));
    }

    #[Test]
    public function a_run_without_ids_cannot_be_played(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BotScript::OpenShops->script(self::login(), ['map' => 'battleon', 'shops' => ['x']]);
    }

    /** @return Generator<mixed, mixed, mixed, mixed> */
    private static function entered(object $script): Generator
    {
        $steps = $script->run();
        $steps->current();
        $steps->send(new ConnectionEstablishedEvent());
        $steps->send(new LoginSucceededEvent(new SocketId(1)));
        $steps->send(
            new MovedToMapEvent(
                new AreaId(1350),
                new RoomId(2),
                new MapName('battleon'),
                new Gamefile('b.swf'),
                [],
                [],
                [],
            ),
        );
        $steps->send(new PlayerInventoryLoadedEvent([]));

        return $steps;
    }

    private static function login(): GameLogin
    {
        return new GameLogin(new PlayerName('Hero'), 'TOKEN', new ClientVersion('5.1'));
    }
}
