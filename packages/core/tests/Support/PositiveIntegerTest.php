<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use Lorekeeper\Core\Support\PositiveInteger;
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
}
