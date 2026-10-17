<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Log;

use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Log\StepLog;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Map\JoinHouseCommand;
use Lorekeeper\Client\Command\Map\JoinInitialMapCommand;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Command\Session\LoginCommand;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Command\Session\PolicyFileRequestCommand;
use Lorekeeper\Client\Command\Shop\LoadShopCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Progression\ClassPoints;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\LoginFailedEvent;
use Lorekeeper\Server\Event\LoginSucceededEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;
use Lorekeeper\Server\RoomId;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\Option;

final class StepLogTest extends TestCase
{
    #[Test]
    public function it_tells_what_happened_in_plain_words(): void
    {
        $login = self::send(new LoginCommand(new PlayerName('Hero'), 'TOKEN', new ClientVersion('5.1')));
        $joinMap = self::send(new JoinMapCommand(new PlayerName('Hero'), new MapName('forest'), Option\none()));

        static::assertSame(
            [LogLevel::Info, 'Connected'],
            StepLog::describe(self::send(new PolicyFileRequestCommand()), new ConnectionEstablishedEvent()),
        );
        static::assertSame(
            [LogLevel::Success, 'Logged in'],
            StepLog::describe($login, new LoginSucceededEvent(new SocketId(1))),
        );
        static::assertSame([LogLevel::Error, 'Login refused'], StepLog::describe($login, new LoginFailedEvent()));
        static::assertSame(
            [LogLevel::Success, 'Entered the game in battleon (room 2)'],
            StepLog::describe(self::send(new JoinInitialMapCommand()), self::moved('battleon', 2)),
        );
        static::assertSame(
            [LogLevel::Success, 'Moved to forest (room 1) · 0 monsters, 0 players'],
            StepLog::describe($joinMap, self::moved('forest', 1)),
        );
        static::assertSame(
            [LogLevel::Success, 'Entered the house in house'],
            StepLog::describe(self::send(new JoinHouseCommand(new PlayerName('Hero'))), self::moved('house', 1)),
        );
        static::assertSame([LogLevel::Warning, 'The map is locked'], StepLog::describe($joinMap, new MapLockedEvent()));
        static::assertSame(
            [LogLevel::Info, 'Inventory loaded: 0 items'],
            StepLog::describe($joinMap, new PlayerInventoryLoadedEvent([])),
        );
    }

    #[Test]
    public function it_warns_about_answers_that_never_came(): void
    {
        $shop = self::send(new LoadShopCommand(new AreaId(1), new ShopId(31)));
        $await = new Await([ConnectionEstablishedEvent::class], Duration::seconds(15));

        static::assertSame(
            [LogLevel::Warning, 'No answer to load shop after 15s'],
            StepLog::describe($shop, new TimedOut($await)),
        );
        static::assertSame(
            [LogLevel::Warning, 'Nothing arrived after 15s'],
            StepLog::describe($await, new TimedOut($await)),
        );
        static::assertSame([LogLevel::Info, 'Connected'], StepLog::describe($await, new ConnectionEstablishedEvent()));
    }

    #[Test]
    public function it_describes_steps_without_answers_and_never_shows_secrets(): void
    {
        $login = self::send(new LoginCommand(new PlayerName('Hero'), 'TOKEN', new ClientVersion('5.1')));

        static::assertSame([LogLevel::Info, 'Logged out'], StepLog::describe(new Send(new LogoutCommand()), null));
        static::assertSame(
            [LogLevel::Info, 'Sent policy file request'],
            StepLog::describe(new Send(new PolicyFileRequestCommand()), null),
        );
        static::assertSame(
            [LogLevel::Info, 'Idling for 60s'],
            StepLog::describe(new Pause(Duration::minutes(1)), null),
        );
        static::assertSame([LogLevel::Info, 'Progress 1/3'], StepLog::describe(new Progress(1, 3), null));
        static::assertSame(
            [LogLevel::Warning, 'Unknown step'],
            StepLog::describe(new class() implements Instruction {}, null),
        );
        static::assertStringNotContainsString(
            'TOKEN',
            StepLog::describe($login, new LoginSucceededEvent(new SocketId(1)))[1],
        );
    }

    #[Test]
    public function it_tells_how_a_fight_goes(): void
    {
        $await = new Await([LeveledUpEvent::class], Duration::seconds(2));

        static::assertSame(
            [LogLevel::Success, 'Reached level 3'],
            StepLog::describe($await, new LeveledUpEvent(new PlayerLevel(3), new Experience(300))),
        );
        static::assertSame([LogLevel::Warning, 'Died'], StepLog::describe($await, new PlayerDiedEvent()));
        static::assertSame(
            [LogLevel::Info, 'Earned 10 experience and 3 gold from monster 4'],
            StepLog::describe(
                $await,
                new MonsterRewardedEvent(new Gold(3), new Experience(10), new ClassPoints(510), new MonsterSpawnId(4)),
            ),
        );
        static::assertSame(
            [LogLevel::Info, 'Monster 4, 5 died'],
            StepLog::describe($await, new MonstersDiedEvent([new MonsterSpawnId(4), new MonsterSpawnId(5)])),
        );
    }

    private static function send(Command $command): Send
    {
        return new Send($command, new Await([ConnectionEstablishedEvent::class], Duration::seconds(15)));
    }

    private static function moved(string $map, int $room): MovedToMapEvent
    {
        return new MovedToMapEvent(
            new AreaId(1),
            new RoomId($room),
            new MapName($map),
            new Gamefile("{$map}.swf"),
            [],
            [],
            [],
        );
    }
}
