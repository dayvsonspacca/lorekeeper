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

final readonly class DropPickedUpEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public ItemId $item,
        public Quantity $picked,
        public Quantity $held,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'getDrop') {
            return Option\none();
        }

        try {
            $pickup = Type\shape([
                'ItemID' => Type\positive_int(),
                'bSuccess' => Type\int(),
                'iQty' => Type\optional(Type\positive_int()),
                'iQtyNow' => Type\optional(Type\positive_int()),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'picked up drop is invalid', $exception);
        }

        if ($pickup['bSuccess'] !== 1) {
            return Option\none();
        }

        if (!array_key_exists('iQty', $pickup) || !array_key_exists('iQtyNow', $pickup)) {
            throw new MalformedEventException(static::class, 'picked up drop has no quantities');
        }

        return Option\some(
            new static(new ItemId($pickup['ItemID']), new Quantity($pickup['iQty']), new Quantity($pickup['iQtyNow'])),
        );
    }

    #[Override]
    public function summary(): string
    {
        return "Picked up {$this->picked->value} of item {$this->item->value}, holding {$this->held->value}";
    }
}
