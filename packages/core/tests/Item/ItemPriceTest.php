<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Item;

use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemPriceTest extends TestCase
{
    #[Test]
    public function it_holds_a_price_in_gold(): void
    {
        $price = new ItemPrice(new Gold(400));

        static::assertInstanceOf(Gold::class, $price->amount);
        static::assertSame(400, $price->amount->value);
    }

    #[Test]
    public function it_holds_a_price_in_adventure_coins(): void
    {
        $price = new ItemPrice(new AdventureCoins(150));

        static::assertInstanceOf(AdventureCoins::class, $price->amount);
        static::assertSame(150, $price->amount->value);
    }
}
