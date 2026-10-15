<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Item;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Protocol\Event\FactEvent;
use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\JsonMessage;
use Lorekeeper\Protocol\Message\Message;
use Lorekeeper\Protocol\SocketId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class ItemEquippedEvent implements FactEvent
{
    /** @param list<Fact<Subject, object>> $facts */
    public function __construct(
        public SocketId $player,
        public ItemId $itemId,
        private array $facts,
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
                'strES' => Type\string(),
            ], allowUnknownFields: true)->coerce($message->data);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'equipped item is missing or invalid', $exception);
        }

        $itemId = new ItemId($data['ItemID']);

        return Option\some(
            new static(new SocketId($data['uid']), $itemId, ItemKindFacts::of($itemId, '', $data['strES'])),
        );
    }

    #[Override]
    public function facts(): array
    {
        return $this->facts;
    }
}
