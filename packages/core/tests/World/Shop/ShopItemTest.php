<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Shop;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\ShopItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShopItemTest extends TestCase
{
    #[Test]
    public function it_holds_an_item_without_costs(): void
    {
        $item = new ShopItem(new ItemId(4887));

        static::assertSame(4887, $item->itemId->value);
        static::assertSame([], $item->costs);
    }

    #[Test]
    public function it_holds_the_merge_costs(): void
    {
        $cost = new MergeCost(new ItemId(17_109), 3);

        static::assertSame([$cost], new ShopItem(new ItemId(17_185), [$cost])->costs);
    }
}
