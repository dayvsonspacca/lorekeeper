<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Routine;

use Lorekeeper\Miner\Routine\ListenRoutine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\DateTime\Duration;
use Psl\TCP;

final class ListenRoutineTest extends TestCase
{
    #[Test]
    public function it_enters_listens_and_logs_out(): void
    {
        $sent = [];

        new ListenRoutine(
            RoutineStub::entry(static::createStub(TCP\StreamInterface::class), $sent, RoutineStub::readySession()),
            static fn(string $line): null => null,
        )->run(Duration::zero());

        static::assertCount(5, $sent);
        static::assertSame('%xt%zm%logout%1%', $sent[4]);
    }
}
