<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Support;

use Lorekeeper\Core\Support\NonEmptyString;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
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

    #[Test]
    public function it_equals_only_the_same_kind_holding_the_same_value(): void
    {
        static::assertTrue(new MapCell('Enter')->equals(new MapCell('Enter')));
        static::assertFalse(new MapCell('Enter')->equals(new MapCell('r2')));
        static::assertFalse(new MapCell('Enter')->equals(new MapName('Enter')));
    }
}
