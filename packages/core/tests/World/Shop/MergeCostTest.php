<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Shop;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Shop\MergeCost;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MergeCostTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $cost = new MergeCost(new ItemId(17_109), new Quantity(3));

        static::assertSame(17_109, $cost->itemId->value);
        static::assertSame(3, $cost->quantity->value);
    }
}
