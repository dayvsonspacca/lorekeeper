<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Traits\Stackable;
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
    /** @param list<HeldItem> $items */
    public function __construct(
        public array $items,
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
                'bWear' => Type\optional(Type\bool()),
            ], allowUnknownFields: true))
                ->coerce($message->data['items'] ?? null);
            $procs = Type\optional(Type\dict(Type\array_key(), Type\mixed()))->coerce($message->data['procs'] ?? []);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $held = [];
        foreach ($items as $entry) {
            if (array_key_exists($entry['ItemID'], $held)) {
                throw new MalformedEventException(static::class, 'an item is in the inventory more than once');
            }

            $item = ItemKinds::read($entry, static::class, $procs);
            if ($entry['bUpg'] ?? false) {
                $item->tags->add(ItemTag::MemberOnly);
            }

            $held[$entry['ItemID']] = new HeldItem(
                $item,
                new Quantity($item instanceof Stackable ? $entry['iQty'] ?? 1 : 1),
                $entry['bEquip'] ?? false,
                $entry['bWear'] ?? false,
            );
        }

        return Option\some(new static(array_values($held)));
    }

    #[Override]
    public function summary(): string
    {
        return 'Inventory loaded: ' . count($this->items) . ' items';
    }
}
