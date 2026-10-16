<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Unit;

use Generator;
use Lorekeeper\Apps\Wiki\Bots\Scripts\StayInMapScript;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\LoginSucceededEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StayInMapScriptTest extends TestCase
{
    #[Test]
    public function it_stays_in_the_first_map_without_asking_for_it_again(): void
    {
        $steps = self::enter('battleon', 'battleon');

        static::assertInstanceOf(Pause::class, $steps->current());
    }

    #[Test]
    public function it_goes_to_another_map_and_stays_there(): void
    {
        $steps = self::enter('battleon', 'forest');

        static::assertInstanceOf(Send::class, $steps->current());
        static::assertSame('%xt%zm%cmd%1%tfer%Hero%forest%', $steps->current()->command->toMessage());

        $steps->send(self::moved('forest'));

        static::assertInstanceOf(Pause::class, $steps->current());
    }

    #[Test]
    public function it_ends_when_the_map_is_refused(): void
    {
        $steps = self::enter('battleon', 'forest');
        $locked = new MapLockedEvent();

        $steps->send($locked);

        static::assertFalse($steps->valid());
        static::assertSame($locked, $steps->getReturn());
    }

    /** @return Generator<mixed, mixed, mixed, mixed> */
    private static function enter(string $first, string $target): Generator
    {
        $steps = new StayInMapScript(
            new GameLogin(new PlayerName('Hero'), 'TOKEN', new ClientVersion('5.1')),
            new MapName($target),
        )->run();

        $steps->current();
        $steps->send(new ConnectionEstablishedEvent());
        $steps->send(new LoginSucceededEvent(new SocketId(1)));
        $steps->send(self::moved($first));
        $steps->send(new PlayerInventoryLoadedEvent([]));

        return $steps;
    }

    private static function moved(string $map): MovedToMapEvent
    {
        return new MovedToMapEvent(
            new AreaId(1),
            new RoomId(1),
            new MapName($map),
            new Gamefile("{$map}.swf"),
            [],
            [],
            [],
        );
    }
}
