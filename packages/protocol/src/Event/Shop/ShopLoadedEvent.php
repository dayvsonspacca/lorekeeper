<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Shop;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Shop\MergeCost;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopItem;
use Lorekeeper\Core\World\Shop\ShopName;
use Lorekeeper\Protocol\Event\FactEvent;
use Lorekeeper\Protocol\Event\Item\ItemFacts;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class ShopLoadedEvent implements FactEvent
{
    /** @param list<Fact<Subject, object>> $facts */
    public function __construct(
        public ShopId $shopId,
        public array $facts,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'loadShop') {
            return Option\none();
        }

        try {
            $shop = Type\shape([
                'ShopID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'bUpgrd' => Type\optional(Type\bool()),
                'items' => Type\vec(ItemFacts::type()),
            ], allowUnknownFields: true)->coerce($message->data['shopinfo'] ?? null);
            $turnins = Type\shape(['items' => Type\vec(self::turninType())], allowUnknownFields: true)->coerce(
                $message->data['shopinfo'],
            )['items'];
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'shopinfo is missing or invalid', $exception);
        }

        $shopId = new ShopId($shop['ShopID']);
        $facts = [new Fact($shopId, new ShopName($shop['sName']))];
        if ($shop['bUpgrd'] ?? false) {
            $facts[] = new Fact($shopId, new MemberOnly());
        }

        foreach ($shop['items'] as $index => $item) {
            $costs = [];
            foreach ($turnins[$index]['turnin'] ?? [] as $material) {
                $costs[] = new MergeCost(new ItemId($material['ItemID']), $material['iQty']);
                $facts[] = new Fact(new ItemId($material['ItemID']), new ItemName($material['sName']));
            }

            $facts = [
                ...$facts,
                new Fact($shopId, new ShopItem(new ItemId($item['ItemID']), $costs)),
                ...ItemFacts::of($item),
            ];
        }

        return Option\some(new static($shopId, $facts));
    }

    /** @return Type\TypeInterface<array{turnin?: list<array{ItemID: positive-int, sName: non-empty-string, iQty: positive-int, ...}>, ...}> */
    private static function turninType(): Type\TypeInterface
    {
        return Type\shape([
            'turnin' => Type\optional(Type\vec(Type\shape([
                'ItemID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'iQty' => Type\positive_int(),
            ], allowUnknownFields: true))),
        ], allowUnknownFields: true);
    }

    #[Override]
    public function facts(): array
    {
        return $this->facts;
    }
}
