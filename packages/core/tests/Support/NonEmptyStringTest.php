<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use Lorekeeper\Core\Support\NonEmptyString;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class NonEmptyStringTest extends TestCase
{
    #[Test]
    public function it_holds_a_non_empty_string(): void
    {
        $string = new readonly class('Battleon') extends NonEmptyString {};

        static::assertSame('Battleon', $string->value);
        static::assertSame('Battleon', (string) $string);
    }

    #[Test]
    public function it_rejects_an_empty_string(): void
    {
        $this->expectException(AssertException::class);

        new readonly class('') extends NonEmptyString {};
    }
}
