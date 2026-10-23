<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\World\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Shop\CurrencyShop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CurrencyShopTest extends TestCase
{
    #[Test]
    public function it_tells_the_price_of_each_item_it_sells(): void
    {
        $price = new ItemPrice(new Gold(5000));
        $shop = new CurrencyShop(new ShopId(1), new ShopName('Battleon Armory'), new Inventory([self::plate()]), [
            16_963 => $price,
        ]);

        static::assertCount(1, $shop->inventory);
        static::assertSame($price, $shop->price(new ItemId(16_963))->unwrap());
        static::assertTrue($shop->price(new ItemId(1))->isNone());
    }

    #[Test]
    public function it_rejects_selling_an_item_without_a_price(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CurrencyShop(new ShopId(1), new ShopName('Battleon Armory'), new Inventory([self::plate()]), []);
    }

    private static function plate(): Armor
    {
        return new Armor(new ItemId(16_963), new ItemName('Plate Armor'), new ItemLevel(1));
    }
}
