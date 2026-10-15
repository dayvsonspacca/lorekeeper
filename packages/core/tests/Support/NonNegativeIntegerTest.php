<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use Lorekeeper\Core\Support\NonNegativeInteger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class NonNegativeIntegerTest extends TestCase
{
    #[Test]
    public function it_holds_a_non_negative_integer(): void
    {
        $integer = new readonly class(0) extends NonNegativeInteger {};

        static::assertSame(0, $integer->value);
        static::assertSame('0', (string) $integer);
    }

    #[Test]
    public function it_rejects_a_negative_integer(): void
    {
        $this->expectException(AssertException::class);

        new readonly class(-1) extends NonNegativeInteger {};
    }
}
