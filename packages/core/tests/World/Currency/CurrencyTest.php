<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Currency;

use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    #[Test]
    public function each_currency_has_its_own_symbol(): void
    {
        static::assertSame('G', new Gold(400)->symbol());
        static::assertSame('AC', new AdventureCoins(150)->symbol());
    }

    #[Test]
    public function gold_is_never_the_same_as_adventure_coins(): void
    {
        static::assertFalse(new Gold(100)->equals(new AdventureCoins(100)));
    }
}
