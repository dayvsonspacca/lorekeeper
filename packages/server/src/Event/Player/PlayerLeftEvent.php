<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\SocketId;
use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class PlayerLeftEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public SocketId $socketId,
        public PlayerName $name,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof DelimitedMessage || ($message->data[1] ?? null) !== 'exitArea') {
            return Option\none();
        }

        try {
            $socketId = Type\positive_int()->coerce($message->data[3] ?? null);
            $name = Type\non_empty_string()->coerce($message->data[4] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'player socket or name is missing or invalid', $exception);
        }

        return Option\some(new static(new SocketId($socketId), new PlayerName($name)));
    }

    #[Override]
    public function summary(): string
    {
        return "{$this->name->value} (socket {$this->socketId->value}) left";
    }
}
