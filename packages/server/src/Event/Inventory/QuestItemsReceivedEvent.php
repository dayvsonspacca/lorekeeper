<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Inventory\Helper\ItemArrival;
use Lorekeeper\Server\Event\Inventory\Helper\ItemArrivals;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

/**
 * Our player received one or more temporary quest items for the first time.
 */
final readonly class QuestItemsReceivedEvent extends InventoryUpdatedEvent
{
    /** @param non-empty-list<PlayerItem> $items */
    public function __construct(
        public array $items,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        $items = [];
        foreach (ItemArrivals::of($message, static::class) ?? [] as [$kind, $entry]) {
            if ($kind !== ItemArrival::QuestItemReceived) {
                continue;
            }

            $items[] = new PlayerItem(ItemKinds::read($entry, static::class), new Quantity($entry['iQty']));
        }

        return $items === [] ? Option\none() : Option\some(new static($items));
    }

    #[Override]
    public function summary(): string
    {
        return 'Got for now '
        . implode(', ', array_map(
            static fn(PlayerItem $held): string => "{$held->quantity->value} {$held->item->name->value}",
            $this->items,
        ));
    }
}
