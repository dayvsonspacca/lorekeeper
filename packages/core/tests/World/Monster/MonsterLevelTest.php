<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Monster;

use Lorekeeper\Core\World\Monster\MonsterLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class MonsterLevelTest extends TestCase
{
    #[Test]
    #[TestWith([1])]
    #[TestWith([255])]
    public function it_accepts_levels_within_range(int $level): void
    {
        static::assertSame($level, new MonsterLevel($level)->value);
    }

    #[Test]
    #[TestWith([0])]
    #[TestWith([256])]
    public function it_rejects_levels_out_of_range(int $level): void
    {
        $this->expectException(AssertException::class);

        new MonsterLevel($level);
    }
}
