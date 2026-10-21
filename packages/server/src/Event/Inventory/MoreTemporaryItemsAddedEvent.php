<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\Helper\AddedItemKind;
use Lorekeeper\Server\Event\Inventory\Helper\AddedItems;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

final readonly class MoreTemporaryItemsAddedEvent implements Event
{
    use NamedAfterClass;

    /** @param non-empty-list<AddedItem> $items */
    public function __construct(
        public array $items,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        $items = [];
        foreach (AddedItems::of($message, static::class) ?? [] as [$kind, $entry]) {
            if ($kind !== AddedItemKind::MoreTemporary) {
                continue;
            }

            $items[] = new AddedItem(new ItemId($entry['ItemID']), new Quantity($entry['iQty']));
        }

        return $items === [] ? Option\none() : Option\some(new static($items));
    }

    #[Override]
    public function summary(): string
    {
        return 'Got for now '
        . implode(', ', array_map(
            static fn(AddedItem $added): string => "{$added->quantity->value} more of item {$added->item->value}",
            $this->items,
        ));
    }
}
