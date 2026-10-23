<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\MergeShop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MergeShopTest extends TestCase
{
    #[Test]
    public function it_keeps_what_merging_each_item_costs(): void
    {
        $gems = new MergeCost(new ItemId(17_109), new Quantity(15));
        $shop = new MergeShop(new ShopId(2), new ShopName('Realm Merge'), new Inventory([self::guardian()]), [16_963 =>
            [$gems]]);

        static::assertSame([$gems], $shop->costs(new ItemId(16_963))->unwrap());
        static::assertTrue($shop->costs(new ItemId(1))->isNone());
    }

    #[Test]
    public function it_rejects_an_item_that_costs_nothing_to_merge(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MergeShop(new ShopId(2), new ShopName('Realm Merge'), new Inventory([self::guardian()]), [16_963 => []]);
    }

    private static function guardian(): Armor
    {
        return new Armor(new ItemId(16_963), new ItemName('Realm Guardian'), new ItemLevel(1));
    }
}
