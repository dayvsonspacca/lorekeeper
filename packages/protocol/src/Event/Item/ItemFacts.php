<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Item;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\ItemDescription;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Knowledge\Fact;
use Psl\Type;

/**
 * @psalm-type Item = array{
 *     ItemID: positive-int,
 *     sName: non-empty-string,
 *     sDesc?: string,
 *     iCost?: non-negative-int,
 *     bCoins?: bool,
 *     sFile?: string,
 *     sLink?: string,
 *     sType?: string,
 *     sES?: string,
 *     bUpg?: bool,
 *     ...
 * }
 */
final readonly class ItemFacts
{
    /** @return Type\TypeInterface<Item> */
    public static function type(): Type\TypeInterface
    {
        return Type\shape([
            'ItemID' => Type\positive_int(),
            'sName' => Type\non_empty_string(),
            'sDesc' => Type\optional(Type\string()),
            'iCost' => Type\optional(Type\uint()),
            'bCoins' => Type\optional(Type\bool()),
            'sFile' => Type\optional(Type\string()),
            'sLink' => Type\optional(Type\string()),
            'sType' => Type\optional(Type\string()),
            'sES' => Type\optional(Type\string()),
            'bUpg' => Type\optional(Type\bool()),
        ], allowUnknownFields: true);
    }

    /**
     * @param Item $item
     *
     * @return list<Fact<ItemId, object>>
     */
    public static function of(array $item): array
    {
        $itemId = new ItemId($item['ItemID']);

        return [
            new Fact($itemId, new ItemName($item['sName'])),
            ...self::descriptionFacts($itemId, $item),
            ...self::priceFacts($itemId, $item),
            ...ItemAssetFacts::of($itemId, $item['sFile'] ?? '', $item['sLink'] ?? ''),
            ...ItemKindFacts::of($itemId, $item['sType'] ?? '', $item['sES'] ?? ''),
            ...($item['bUpg'] ?? false ? [new Fact($itemId, new MemberOnly())] : []),
        ];
    }

    /**
     * @param Item $item
     *
     * @return list<Fact<ItemId, ItemDescription>>
     */
    private static function descriptionFacts(ItemId $itemId, array $item): array
    {
        $description = $item['sDesc'] ?? '';

        return trim($description) === '' ? [] : [new Fact($itemId, new ItemDescription($description))];
    }

    /**
     * @param Item $item
     *
     * @return list<Fact<ItemId, ItemPrice>>
     */
    private static function priceFacts(ItemId $itemId, array $item): array
    {
        $cost = $item['iCost'] ?? 0;
        if ($cost === 0) {
            return [];
        }

        $amount = $item['bCoins'] ?? false ? new AdventureCoins($cost) : new Gold($cost);

        return [new Fact($itemId, new ItemPrice($amount))];
    }
}
