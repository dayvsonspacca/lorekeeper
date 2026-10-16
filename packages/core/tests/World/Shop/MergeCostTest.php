<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Shop;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Shop\MergeCost;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

final class MergeCostTest extends TestCase
{
    #[Test]
    public function it_holds_item_and_quantity(): void
    {
        $cost = new MergeCost(new ItemId(17_109), 3);

        static::assertSame(17_109, $cost->itemId->value);
        static::assertSame(3, $cost->quantity);
    }

    #[Test]
    public function it_rejects_a_zero_quantity(): void
    {
        $this->expectException(AssertException::class);

        new MergeCost(new ItemId(17_109), 0);
    }
}
