<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use Lorekeeper\Miner\Routine\HouseRoutine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Psl\TCP;

final class HouseRoutineTest extends TestCase
{
    #[Test]
    public function it_enters_goes_home_and_logs_out_when_stopped(): void
    {
        $sent = [];
        $stop = new SignalCancellationToken();
        Scheduler::delay(Duration::milliseconds(2100), static function () use ($stop): void {
            $stop->cancel();
        });

        new HouseRoutine(
            RoutineStub::entry(static::createStub(TCP\StreamInterface::class), $sent, RoutineStub::readySession()),
            static fn(string $line): null => null,
        )->run($stop);

        static::assertCount(6, $sent);
        static::assertSame('%xt%zm%house%1%Hero%', $sent[4]);
        static::assertSame('%xt%zm%logout%1%', $sent[5]);
    }
}
