<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Item;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * A player in the room equipped an item.
 */
final readonly class ItemEquippedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public PlayerSpawnId $player,
        public ItemId $itemId,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'equipItem') {
            return Option\none();
        }

        try {
            $data = Type\shape([
                'uid' => Type\positive_int(),
                'ItemID' => Type\positive_int(),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'equipped item is missing or invalid', $exception);
        }

        return Option\some(new static(new PlayerSpawnId($data['uid']), new ItemId($data['ItemID'])));
    }

    #[Override]
    public function summary(): string
    {
        return "Spawn {$this->player->value} equipped item {$this->itemId->value}";
    }
}
