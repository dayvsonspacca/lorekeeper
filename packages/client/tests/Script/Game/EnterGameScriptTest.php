<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Game\EnterGameScript;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Session\LoginFailedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class EnterGameScriptTest extends TestCase
{
    #[Test]
    public function it_logs_in_joins_the_first_map_and_loads_the_inventory(): void
    {
        $battleon = self::battleon();

        [$instructions, $result] = Replay::run(
            self::script(),
            new ConnectionEstablishedEvent(),
            new LoginSucceededEvent(new SocketId(59_878)),
            $battleon,
            new PlayerInventoryLoadedEvent([]),
        );

        static::assertEquals(new InGame(new SocketId(59_878), $battleon), $result);
        static::assertSame(
            [
                '<policy-file-request/>',
                "<msg t='sys'><body action='login' r='0'><login z='zone_master'><nick><![CDATA[SPIDER#0001~Hero~5.1]]></nick><pword><![CDATA[TOKEN]]></pword></login></body></msg>",
                '%xt%zm%firstJoin%1%',
                '%xt%zm%retrieveInventory%1350%59878%',
            ],
            array_map(static fn(Send $send): string => $send->command->toMessage(), $instructions),
        );
        static::assertEquals(
            new Await([LoginSucceededEvent::class, LoginFailedEvent::class], Duration::seconds(15)),
            $instructions[1]->await,
        );
    }

    #[Test]
    public function it_stops_when_the_login_is_refused(): void
    {
        [$instructions, $result] = Replay::run(
            self::script(),
            new ConnectionEstablishedEvent(),
            new LoginFailedEvent(),
        );

        static::assertInstanceOf(LoginFailedEvent::class, $result);
        static::assertCount(2, $instructions);
    }

    #[Test]
    public function it_stops_at_the_first_wait_that_runs_out(): void
    {
        $timedOut = new TimedOut(new Await([ConnectionEstablishedEvent::class], Duration::seconds(15)));
        $login = new LoginSucceededEvent(new SocketId(1));

        static::assertSame($timedOut, Replay::run(self::script(), $timedOut)[1]);
        static::assertSame(
            $timedOut,
            Replay::run(self::script(), new ConnectionEstablishedEvent(), $login, $timedOut)[1],
        );
        static::assertSame(
            $timedOut,
            Replay::run(self::script(), new ConnectionEstablishedEvent(), $login, self::battleon(), $timedOut)[1],
        );
    }

    private static function script(): EnterGameScript
    {
        return new EnterGameScript(new GameLogin(new PlayerName('Hero'), 'TOKEN', new ClientVersion('5.1')));
    }

    private static function battleon(): MovedToMapEvent
    {
        return new MovedToMapEvent(
            new AreaId(1350),
            new RoomId(2),
            new MapName('battleon'),
            new Gamefile('Battleon/town-Battleon-9Oct26.swf'),
        );
    }
}
