<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Session;

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

final readonly class LoginSucceededEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public SocketId $socketId,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof DelimitedMessage || ($message->data[1] ?? null) !== 'loginResponse') {
            return Option\none();
        }

        if (($message->data[3] ?? null) !== 'true') {
            return Option\none();
        }

        try {
            $socketId = Type\positive_int()->coerce($message->data[4] ?? null);
        } catch (CoercionException $exception) {
            throw new MalformedEventException(static::class, 'socket id is missing or invalid', $exception);
        }

        return Option\some(new static(new SocketId($socketId)));
    }

    #[Override]
    public function summary(): string
    {
        return "Logged in as socket {$this->socketId->value}";
    }
}
