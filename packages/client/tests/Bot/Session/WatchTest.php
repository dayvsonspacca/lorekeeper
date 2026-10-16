<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Bot\Session;

use Lorekeeper\Client\Bot\Session\Watch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Async;
use Psl\DateTime\Duration;

final class WatchTest extends TestCase
{
    #[Test]
    public function it_cancels_the_token_once_the_condition_holds(): void
    {
        $checks = 0;
        $watch = Watch::until(static function () use (&$checks): bool {
            return ++$checks >= 2;
        }, Duration::milliseconds(5));

        Async\sleep(Duration::milliseconds(50));

        static::assertTrue($watch->token()->isCancelled());
        static::assertSame(2, $checks);
        $watch->release();
    }

    #[Test]
    public function releasing_it_stops_watching_without_cancelling(): void
    {
        $checks = 0;
        $watch = Watch::until(static function () use (&$checks): bool {
            $checks++;

            return true;
        }, Duration::seconds(10));

        $watch->release();

        static::assertFalse($watch->token()->isCancelled());
        static::assertSame(0, $checks);
    }
}
