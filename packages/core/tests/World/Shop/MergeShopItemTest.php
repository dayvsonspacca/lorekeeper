<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\MergeShopItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MergeShopItemTest extends TestCase
{
    #[Test]
    public function it_keeps_what_merging_the_item_costs(): void
    {
        $gems = new MergeCost(new ItemId(17_109), new Quantity(15));
        $guardian = new MergeShopItem(
            new Armor(new ItemId(16_963), new ItemName('Realm Guardian'), new ItemLevel(1)),
            [$gems],
        );

        static::assertSame([$gems], $guardian->costs);
    }

    #[Test]
    public function it_rejects_an_item_that_costs_nothing_to_merge(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MergeShopItem(new Armor(new ItemId(16_963), new ItemName('Realm Guardian'), new ItemLevel(1)), []);
    }
}
