<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Item\ItemKinds;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerInventoryLoadedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public PlayerInventory $inventory,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'loadInventoryBig') {
            return Option\none();
        }

        try {
            $items = Type\vec(Type\shape([
                'ItemID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'sType' => Type\string(),
                'iQty' => Type\optional(Type\positive_int()),
                'bUpg' => Type\optional(Type\bool()),
            ], allowUnknownFields: true))
                ->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $held = [];
        foreach ($items as $entry) {
            $item = ItemKinds::read(
                new ItemId($entry['ItemID']),
                new ItemName($entry['sName']),
                $entry['sType'],
                static::class,
            );
            if ($entry['bUpg'] ?? false) {
                $item->tags->add(ItemTag::MemberOnly);
            }

            $held[] = new PlayerItem(
                $item,
                $item instanceof ClassItem ? new Quantity(1) : new Quantity($entry['iQty'] ?? 1),
            );
        }

        try {
            return Option\some(new static(new PlayerInventory($held)));
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(static::class, 'an item is in the inventory more than once', $exception);
        }
    }

    #[Override]
    public function summary(): string
    {
        return 'Inventory loaded: ' . count($this->inventory->items) . ' items';
    }
}
