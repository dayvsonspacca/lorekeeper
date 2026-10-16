<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Unit;

use Lorekeeper\Apps\Wiki\Bots\StepLog;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Command\LoginCommand;
use Lorekeeper\Client\Command\LogoutCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\LoginSucceededEvent;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;

final class StepLogTest extends TestCase
{
    #[Test]
    public function it_describes_each_step_by_name_only(): void
    {
        $login = new Send(
            new LoginCommand(new PlayerName('Hero'), 'TOKEN', new ClientVersion('5.1')),
            new Await([LoginSucceededEvent::class], Duration::seconds(15)),
        );
        $await = new Await([ConnectionEstablishedEvent::class], Duration::seconds(15));

        static::assertSame('> Login < LoginSucceeded', StepLog::describe(
            $login,
            new LoginSucceededEvent(new SocketId(1)),
        ));
        static::assertSame('> Logout', StepLog::describe(new Send(new LogoutCommand()), null));
        static::assertSame('… < ConnectionEstablished', StepLog::describe($await, new ConnectionEstablishedEvent()));
        static::assertSame('… … timed out after 15s', StepLog::describe($await, new TimedOut($await)));
        static::assertSame('… paused 60s', StepLog::describe(new Pause(Duration::minutes(1)), null));
        static::assertStringStartsWith('? Instruction@anonymous', StepLog::describe(new class() implements
            Instruction {}, null));
        static::assertStringNotContainsString('TOKEN', StepLog::describe($login, null));
    }
}
