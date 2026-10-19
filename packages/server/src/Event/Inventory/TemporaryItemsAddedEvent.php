<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Player\PlayerItem;
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

final readonly class TemporaryItemsAddedEvent implements Event
{
    use NamedAfterClass;

    /** @param non-empty-list<PlayerItem> $items */
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
            $items = Type\dict(Type\string(), Type\shape([
                'ItemID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'sType' => Type\string(),
                'bTemp' => Type\bool(),
                'iQty' => Type\positive_int(),
            ], allowUnknownFields: true))->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $added = [];
        foreach ($items as $entry) {
            if (!$entry['bTemp']) {
                continue;
            }

            $added[] = new PlayerItem(
                ItemKinds::read(
                    new ItemId($entry['ItemID']),
                    new ItemName($entry['sName']),
                    $entry['sType'],
                    static::class,
                ),
                new Quantity($entry['iQty']),
            );
        }

        return $added === [] ? Option\none() : Option\some(new static($added));
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
