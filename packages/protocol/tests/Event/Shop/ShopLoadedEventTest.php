<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Shop;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopItem;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Protocol\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShopLoadedEventTest extends TestCase
{
    #[Test]
    public function it_records_the_shop_and_what_it_sells(): void
    {
        $event = ShopLoadedEvent::from(self::message([
            'ShopID' => 31,
            'sName' => 'Battleon Assorted shop',
            'bUpgrd' => false,
            'items' => [
                [
                    'ItemID' => 16_416,
                    'sName' => 'Gladiator of Circumstance',
                    'iCost' => 450,
                    'bCoins' => true,
                    'bUpg' => true,
                ],
            ],
        ]))->unwrap();

        static::assertEquals(new ShopId(31), $event->shopId);
        static::assertEquals(
            [
                new Fact(new ShopId(31), new ShopName('Battleon Assorted shop')),
                new Fact(new ShopId(31), new ShopItem(new ItemId(16_416))),
                new Fact(new ItemId(16_416), new ItemName('Gladiator of Circumstance')),
                new Fact(new ItemId(16_416), new ItemPrice(new AdventureCoins(450))),
                new Fact(new ItemId(16_416), new MemberOnly()),
            ],
            $event->facts,
        );
    }

    #[Test]
    public function it_records_what_merge_shops_ask_in_exchange(): void
    {
        $event = ShopLoadedEvent::from(self::message([
            'ShopID' => 2081,
            'sName' => 'Legacy Generosity Shop',
            'bMerge' => true,
            'items' => [
                [
                    'ItemID' => 66_472,
                    'sName' => 'Titanium FrostElf Royalty',
                    'turnin' => [['ItemID' => 66_486, 'sName' => 'Generosity Coin', 'iQty' => 2]],
                ],
            ],
        ]))->unwrap();

        static::assertEquals(
            [
                new Fact(new ShopId(2081), new ShopName('Legacy Generosity Shop')),
                new Fact(new ItemId(66_486), new ItemName('Generosity Coin')),
                new Fact(new ShopId(2081), new ShopItem(new ItemId(66_472), [new MergeCost(new ItemId(66_486), 2)])),
                new Fact(new ItemId(66_472), new ItemName('Titanium FrostElf Royalty')),
            ],
            $event->facts,
        );
    }

    #[Test]
    public function it_marks_member_shops(): void
    {
        $event = ShopLoadedEvent::from(self::message([
            'ShopID' => 2,
            'sName' => 'Members shop',
            'bUpgrd' => true,
            'items' => [],
        ]))->unwrap();

        static::assertEquals(new Fact(new ShopId(2), new MemberOnly()), $event->facts[1]);
    }

    #[Test]
    public function it_ignores_other_commands(): void
    {
        static::assertTrue(ShopLoadedEvent::from(new JsonMessage(['cmd' => 'getQuests'], ''))->isNone());
    }

    #[Test]
    public function it_rejects_a_missing_shopinfo(): void
    {
        $this->expectException(MalformedEventException::class);

        ShopLoadedEvent::from(new JsonMessage(['cmd' => 'loadShop'], ''));
    }

    /** @param array<string, mixed> $shopinfo */
    private static function message(array $shopinfo): JsonMessage
    {
        return new JsonMessage(['cmd' => 'loadShop', 'shopinfo' => $shopinfo], '');
    }
}
