<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Shop;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
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
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'shopinfo is missing or invalid', $exception);
        }

        $shopId = new ShopId($shop['ShopID']);
        $facts = [new Fact($shopId, new ShopName($shop['sName']))];
        if ($shop['bUpgrd'] ?? false) {
            $facts[] = new Fact($shopId, new MemberOnly());
        }

        foreach ($shop['items'] as $item) {
            $facts = [...$facts, new Fact($shopId, new ShopItem(new ItemId($item['ItemID']))), ...ItemFacts::of($item)];
        }

        return Option\some(new static($shopId, $facts));
    }

    #[Override]
    public function facts(): array
    {
        return $this->facts;
    }
}
