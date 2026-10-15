<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Protocol\Event\Item\ItemFacts;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerInventoryLoadedEvent implements FactEvent
{
    /** @param list<Fact<ItemId, object>> $facts */
    public function __construct(
        public array $facts,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'loadInventoryBig') {
            return Option\none();
        }

        $type = Type\vec(ItemFacts::type());

        try {
            $items = $type->coerce($message->data['items'] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'items are missing or invalid', $exception);
        }

        $facts = [];
        foreach ($items as $item) {
            $facts = [...$facts, ...ItemFacts::of($item)];
        }

        return Option\some(new static($facts));
    }

    #[Override]
    public function facts(): array
    {
        return $this->facts;
    }
}
