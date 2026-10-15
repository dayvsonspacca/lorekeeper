<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Cli;

use Lorekeeper\Miner\Cli\Arguments;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArgumentsTest extends TestCase
{
    #[Test]
    public function it_reads_the_routine_and_options(): void
    {
        $arguments = Arguments::parse(['mine.php', 'listen', '--user=Hero', '--duration=30', 'stray']);

        static::assertSame('listen', $arguments->routine);
        static::assertTrue($arguments->has('user'));
        static::assertSame('Hero', $arguments->get('user'));
        static::assertSame('30', $arguments->get('duration'));
        static::assertFalse($arguments->has('server'));
        static::assertSame('Twilly', $arguments->get('server', 'Twilly'));
    }

    #[Test]
    public function it_handles_no_routine(): void
    {
        static::assertSame('', Arguments::parse(['mine.php'])->routine);
    }
}
