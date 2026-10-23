<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Inventory\InventoryUpdatedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * Items left our player's inventory, turned in to complete a quest.
 */
final readonly class ItemsTurnedInEvent extends InventoryUpdatedEvent
{
    /** @param non-empty-list<TurnedInItem> $items */
    public function __construct(
        public array $items,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'turnIn') {
            return Option\none();
        }

        try {
            $listed = Type\non_empty_string()->coerce($message->data['sItems'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'turned in items are missing', $exception);
        }

        $items = [];
        foreach (explode(',', $listed) as $entry) {
            $parts = [];
            if (preg_match('/^([1-9]\d*):([1-9]\d*)$/', $entry, $parts) !== 1) {
                throw new MalformedEventException(static::class, "turned in item '{$entry}' is not id:quantity");
            }

            $items[] = new TurnedInItem(new ItemId((int) $parts[1]), new Quantity((int) $parts[2]));
        }

        return Option\some(new static($items));
    }

    #[Override]
    public function summary(): string
    {
        return 'Turned in '
        . implode(', ', array_map(
            static fn(TurnedInItem $item): string => "{$item->quantity->value} of item {$item->item->value}",
            $this->items,
        ));
    }
}
