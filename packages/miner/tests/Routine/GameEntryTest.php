<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Miner\Session;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\TCP;

final class GameEntryTest extends TestCase
{
    #[Test]
    public function it_logs_in_joins_and_loads_the_inventory(): void
    {
        $sent = [];

        RoutineStub::entry(static::createStub(TCP\StreamInterface::class), $sent, RoutineStub::readySession())->enter();

        static::assertCount(4, $sent);
        static::assertSame('<policy-file-request/>', $sent[0]);
        static::assertStringContainsString('SPIDER#0001~Hero~5.1', $sent[1]);
        static::assertSame('%xt%zm%firstJoin%1%', $sent[2]);
        static::assertSame('%xt%zm%retrieveInventory%172760%59878%', $sent[3]);
    }

    #[Test]
    public function it_gives_up_when_the_session_never_logs_in(): void
    {
        $sent = [];

        $this->expectException(SessionNotReadyException::class);

        RoutineStub::entry(
            static::createStub(TCP\StreamInterface::class),
            $sent,
            new Session(),
            Duration::milliseconds(200),
        )->enter();
    }
}
