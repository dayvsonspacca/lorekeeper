<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Item\Helper\Enhancements;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class ItemsEnhancedEvent implements Event
{
    use NamedAfterClass;

    /** @param non-empty-list<ItemId> $items */
    public function __construct(
        public array $items,
        public Enhancement $enhancement,
        public Gold $paid,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'enhanceItemShop') {
            return Option\none();
        }

        try {
            $answer = Type\shape([
                'bSuccess' => Type\int(),
                'ItemIDs' => Type\optional(Type\non_empty_vec(Type\positive_int())),
                'EnhPID' => Type\optional(Type\int()),
                'EnhLvl' => Type\optional(Type\int_range(min: 1, max: 100)),
                'EnhRty' => Type\optional(Type\int()),
                'iCost' => Type\optional(Type\uint()),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'the enhanced items are invalid', $exception);
        }

        if ($answer['bSuccess'] !== 1) {
            return Option\none();
        }

        $type = Enhancements::type($answer['EnhPID'] ?? 0);
        $rarity = Enhancements::rarity($answer['EnhRty'] ?? 0);
        $items = $answer['ItemIDs'] ?? null;
        $level = $answer['EnhLvl'] ?? null;
        if ($type === null || $rarity === null || $items === null || $level === null) {
            throw new MalformedEventException(
                static::class,
                'a successful enhancement names no items, type, rarity or level',
            );
        }

        return Option\some(
            new static(
                array_map(static fn(int $item): ItemId => new ItemId($item), $items),
                new Enhancement($type, $rarity, new ItemLevel($level), Option\none()),
                new Gold($answer['iCost'] ?? 0),
            ),
        );
    }

    #[Override]
    public function summary(): string
    {
        return (
            count($this->items)
            . " item(s) enhanced with {$this->enhancement->type->name} level {$this->enhancement->level->value}"
        );
    }
}
