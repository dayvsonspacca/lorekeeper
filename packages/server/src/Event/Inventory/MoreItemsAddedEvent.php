<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class MoreItemsAddedEvent implements Event
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
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'addItems') {
            return Option\none();
        }

        try {
            $items = Type\dict(Type\positive_int(), Type\shape([
                'ItemID' => Type\optional(Type\positive_int()),
                'iQty' => Type\positive_int(),
                'iQtyNow' => Type\optional(Type\positive_int()),
                'sName' => Type\optional(Type\string()),
            ], allowUnknownFields: true))->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $added = [];
        foreach ($items as $id => $entry) {
            if (array_key_exists('sName', $entry)) {
                continue;
            }

            $added[] = new AddedItem(
                new ItemId($entry['ItemID'] ?? $id),
                new Quantity($entry['iQty']),
                array_key_exists('iQtyNow', $entry) ? new Quantity($entry['iQtyNow']) : null,
            );
        }

        return $added === [] ? Option\none() : Option\some(new static($added));
    }

    #[Override]
    public function summary(): string
    {
        return 'Got '
        . implode(', ', array_map(
            static fn(AddedItem $added): string => (
                "{$added->quantity->value} more of item {$added->item->value}"
                . ($added->held === null ? '' : ", {$added->held->value} held now")
            ),
            $this->items,
        ));
    }
}
