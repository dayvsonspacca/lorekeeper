<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Shop;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Inventory;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use Lorekeeper\Core\World\Shop\CurrencyShop;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\MergeShop;
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
 * The server sent a shop and the items it sells.
 *
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

        $items = [];
        $prices = [];
        $costs = [];
        foreach ($info['items'] as $entry) {
            $items[] = self::item($entry);
            $prices[$entry['ItemID']] = self::price($entry);
            $costs[$entry['ItemID']] = self::costs($entry);
        }

        try {
            /** @var Inventory<Item> $inventory */
            $inventory = new Inventory($items);
            $shop = $info['bMerge'] ?? false
                ? new MergeShop($id, $name, $inventory, $costs)
                : new CurrencyShop($id, $name, $inventory, $prices);
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
        $count = $shop instanceof CurrencyShop || $shop instanceof MergeShop ? count($shop->inventory) : 0;

        return "{$shop->name->value}: {$count} items";
    }

    /** @param ShopEntry $entry */
    private static function price(array $entry): ItemPrice
    {
        $cost = $entry['iCost'] ?? 0;

        return new ItemPrice($entry['bCoins'] ?? false ? new AdventureCoins($cost) : new Gold($cost));
    }

    /**
     * @param ShopEntry $entry
     *
     * @return list<MergeCost>
     */
    private static function costs(array $entry): array
    {
        return array_map(
            static fn(array $material): MergeCost => new MergeCost(
                new ItemId($material['ItemID']),
                new Quantity($material['iQty']),
            ),
            $entry['turnin'] ?? [],
        );
    }

    /**
     * @param ShopEntry $entry
     *
     * @throws MalformedEventException
     */
    private static function item(array $entry): Item
    {
        $item = ItemKinds::read($entry, static::class);

        if ($entry['bUpg'] ?? false) {
            $item->tags->add(ItemTag::MemberOnly);
        }

        return $item;
    }
}
