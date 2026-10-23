<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Inventory\Helper\ItemArrival;
use Lorekeeper\Server\Event\Inventory\Helper\ItemArrivals;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

/**
 * Our player got more of one or more items they already had in their inventory.
 */
final readonly class ItemsIncreasedEvent extends InventoryUpdatedEvent
{
    /** @param non-empty-list<ItemIncrease> $items */
    public function __construct(
        public array $items,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        $items = [];
        foreach (ItemArrivals::of($message, static::class) ?? [] as [$kind, $entry]) {
            $held = $entry['iQtyNow'] ?? null;
            if ($kind !== ItemArrival::ItemIncreased || $held === null) {
                continue;
            }

            $items[] = new ItemIncrease(
                new ItemId($entry['ItemID']),
                new Quantity($entry['iQty']),
                new Quantity($held),
            );
        }

        return $items === [] ? Option\none() : Option\some(new static($items));
    }

    #[Override]
    public function summary(): string
    {
        return 'Got '
        . implode(', ', array_map(
            static fn(ItemIncrease $added): string => (
                "{$added->quantity->value} more of item {$added->item->value}"
                . ($added->held === null ? '' : ", {$added->held->value} held now")
            ),
            $this->items,
        ));
    }
}
