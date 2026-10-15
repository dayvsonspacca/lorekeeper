<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player;

use Lorekeeper\Core\Player\PlayerLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class PlayerLevelTest extends TestCase
{
    #[Test]
    #[TestWith([1])]
    #[TestWith([100])]
    public function it_accepts_levels_within_range(int $level): void
    {
        static::assertSame($level, new PlayerLevel($level)->value);
    }

    #[Test]
    #[TestWith([0])]
    #[TestWith([101])]
    public function it_rejects_levels_out_of_range(int $level): void
    {
        $this->expectException(AssertException::class);

        new PlayerLevel($level);
    }
}
