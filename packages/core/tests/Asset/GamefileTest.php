<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Asset;

use InvalidArgumentException;
use Lorekeeper\Core\Asset\Gamefile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class GamefileTest extends TestCase
{
    #[Test]
    public function it_holds_a_swf_path(): void
    {
        $gamefile = new Gamefile('mon/Skeleton.swf');

        static::assertSame('mon/Skeleton.swf', $gamefile->value);
        static::assertSame('mon/Skeleton.swf', (string) $gamefile);
    }

    #[Test]
    public function it_rejects_a_non_swf_path(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Gamefile('mon/Skeleton.png');
    }

    #[Test]
    public function it_rejects_an_empty_path(): void
    {
        $this->expectException(AssertException::class);

        new Gamefile('');
    }
}
