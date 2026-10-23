<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\PositiveInteger;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class PositiveIntegerTest extends TestCase
{
    #[Test]
    public function it_holds_a_positive_integer(): void
    {
        $integer = new readonly class(1) extends PositiveInteger {};

        static::assertSame(1, $integer->value);
        static::assertSame('1', (string) $integer);
    }

    #[Test]
    public function it_rejects_zero(): void
    {
        $this->expectException(AssertException::class);

        new readonly class(0) extends PositiveInteger {};
    }

    #[Test]
    public function it_equals_only_the_same_kind_holding_the_same_value(): void
    {
        static::assertTrue(new MonsterSpawnId(1)->equals(new MonsterSpawnId(1)));
        static::assertFalse(new MonsterSpawnId(1)->equals(new MonsterSpawnId(2)));
        static::assertFalse(new MonsterSpawnId(1)->equals(new ItemId(1)));
    }
}
