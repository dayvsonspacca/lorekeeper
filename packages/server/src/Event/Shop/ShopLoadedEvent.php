<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Shop\CurrencyShop;
use Lorekeeper\Core\World\Shop\CurrencyShopInventory;
use Lorekeeper\Core\World\Shop\CurrencyShopItem;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\MergeShop;
use Lorekeeper\Core\World\Shop\MergeShopInventory;
use Lorekeeper\Core\World\Shop\MergeShopItem;
use Lorekeeper\Core\World\Shop\Shop;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Core\World\Shop\ShopTag;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * @psalm-type ShopEntry = array{ItemID: positive-int, sName: non-empty-string, sType: string, iCost?: non-negative-int, bCoins?: bool, bUpg?: bool, turnin?: list<array{ItemID: positive-int, iQty: positive-int, ...}>, ...}
 *
 * @mago-expect lint:cyclomatic-complexity
 */
final readonly class ShopLoadedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public Shop $shop,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'loadShop') {
            return Option\none();
        }

        try {
            $info = Type\shape([
                'ShopID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'bUpgrd' => Type\optional(Type\bool()),
                'bMerge' => Type\optional(Type\bool()),
                'bHouse' => Type\optional(Type\bool()),
                'items' => Type\vec(Type\shape([
                    'ItemID' => Type\positive_int(),
                    'sName' => Type\non_empty_string(),
                    'sType' => Type\string(),
                    'iCost' => Type\optional(Type\uint()),
                    'bCoins' => Type\optional(Type\bool()),
                    'bUpg' => Type\optional(Type\bool()),
                    'turnin' => Type\optional(Type\vec(Type\shape([
                        'ItemID' => Type\positive_int(),
                        'iQty' => Type\positive_int(),
                    ], allowUnknownFields: true))),
                ], allowUnknownFields: true)),
            ], allowUnknownFields: true)->coerce($message->data['shopinfo'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'shopinfo is missing or invalid', $exception);
        }

        $id = new ShopId($info['ShopID']);
        $name = new ShopName($info['sName']);

        try {
            $shop = $info['bMerge'] ?? false
                ? new MergeShop($id, $name, new MergeShopInventory(array_map(self::merged(...), $info['items'])))
                : new CurrencyShop($id, $name, new CurrencyShopInventory(array_map(self::sold(...), $info['items'])));
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(static::class, $exception->getMessage(), $exception);
        }

        if ($info['bUpgrd'] ?? false) {
            $shop->tags->add(ShopTag::MemberOnly);
        }

        if ($info['bHouse'] ?? false) {
            $shop->tags->add(ShopTag::House);
        }

        return Option\some(new static($shop));
    }

    #[Override]
    public function summary(): string
    {
        $shop = $this->shop;
        $count = $shop instanceof CurrencyShop || $shop instanceof MergeShop ? count($shop->inventory->items) : 0;

        return "{$shop->name->value}: {$count} items";
    }

    /**
     * @param ShopEntry $entry
     *
     * @throws MalformedEventException
     */
    private static function sold(array $entry): CurrencyShopItem
    {
        $cost = $entry['iCost'] ?? 0;

        return new CurrencyShopItem(
            self::item($entry),
            new ItemPrice($entry['bCoins'] ?? false ? new AdventureCoins($cost) : new Gold($cost)),
        );
    }

    /**
     * @param ShopEntry $entry
     *
     * @throws MalformedEventException
     * @throws InvalidArgumentException
     */
    private static function merged(array $entry): MergeShopItem
    {
        return new MergeShopItem(
            self::item($entry),
            array_map(
                static fn(array $material): MergeCost => new MergeCost(
                    new ItemId($material['ItemID']),
                    new Quantity($material['iQty']),
                ),
                $entry['turnin'] ?? [],
            ),
        );
    }

    /**
     * @param ShopEntry $entry
     *
     * @throws MalformedEventException
     */
    private static function item(array $entry): Item
    {
        $item = ItemKinds::read(
            new ItemId($entry['ItemID']),
            new ItemName($entry['sName']),
            $entry['sType'],
            static::class,
        );

        if ($entry['bUpg'] ?? false) {
            $item->tags->add(ItemTag::MemberOnly);
        }

        return $item;
    }
}
