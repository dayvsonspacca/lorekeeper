<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script\Game;

use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Script\Game\LogoutScript;
use Lorekeeper\Client\Script\Send;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogoutScriptTest extends TestCase
{
    #[Test]
    public function it_logs_out_without_waiting(): void
    {
        static::assertEquals([[new Send(new LogoutCommand())], null], Replay::run(new LogoutScript()));
    }
}
