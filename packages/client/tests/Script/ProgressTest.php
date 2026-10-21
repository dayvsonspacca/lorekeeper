<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Script;

use Lorekeeper\Client\Script\Progress;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProgressTest extends TestCase
{
    #[Test]
    public function it_tells_how_much_is_done_without_going_past_all_of_it(): void
    {
        static::assertSame(0.25, new Progress(1, 4)->ratio());
        static::assertSame(1.0, new Progress(6, 4)->ratio());
    }
}
