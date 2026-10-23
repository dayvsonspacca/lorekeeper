<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Item\Helper\ItemKinds;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * One or more items landed in our player's drop list, waiting to be picked up.
 */
final readonly class ItemDroppedEvent implements Event
{
    use NamedAfterClass;

    /** @param non-empty-list<HeldItem> $items */
    public function __construct(
        public array $items,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'dropItem') {
            return Option\none();
        }

        try {
            $items = Type\non_empty_dict(Type\string(), Type\shape([
                'ItemID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'sType' => Type\string(),
                'iQty' => Type\positive_int(),
                'bUpg' => Type\optional(Type\bool()),
            ], allowUnknownFields: true))->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'dropped items are missing or invalid', $exception);
        }

        $dropped = [];
        foreach ($items as $entry) {
            $item = ItemKinds::read($entry, static::class);
            if ($entry['bUpg'] ?? false) {
                $item->tags->add(ItemTag::MemberOnly);
            }

            $dropped[] = new HeldItem($item, new Quantity($entry['iQty']));
        }

        return Option\some(new static($dropped));
    }

    #[Override]
    public function summary(): string
    {
        return 'Dropped '
        . implode(', ', array_map(
            static fn(HeldItem $held): string => "{$held->quantity->value} {$held->item->name->value}",
            $this->items,
        ));
    }
}
