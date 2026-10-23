<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * The server sent every item in our player's inventory.
 */
final readonly class InventoryLoadedEvent extends InventoryUpdatedEvent
{
    public function __construct(
        public PlayerItems $items,
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
                'bEquip' => Type\optional(Type\bool()),
            ], allowUnknownFields: true))
                ->coerce($message->data['items'] ?? null);
            $procs = Type\optional(Type\dict(Type\array_key(), Type\mixed()))->coerce($message->data['procs'] ?? []);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $held = [];
        foreach ($items as $entry) {
            $item = ItemKinds::read($entry, static::class, $procs);
            if ($entry['bUpg'] ?? false) {
                $item->tags->add(ItemTag::MemberOnly);
            }

            $held[] = new PlayerItem(
                $item,
                $item instanceof ClassItem ? new Quantity(1) : new Quantity($entry['iQty'] ?? 1),
                $entry['bEquip'] ?? false,
            );
        }

        try {
            return Option\some(new static(new PlayerItems($held)));
        } catch (InvalidArgumentException $exception) {
            throw new MalformedEventException(static::class, 'an item is in the inventory more than once', $exception);
        }
    }

    #[Override]
    public function summary(): string
    {
        return 'Inventory loaded: ' . count($this->items->items) . ' items';
    }
}
