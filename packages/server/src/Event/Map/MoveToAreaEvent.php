<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

abstract readonly class MoveToAreaEvent implements Event
{
    /**
     * @param array<array-key, mixed> $data
     *
     * @throws MalformedEventException
     */
    abstract protected static function read(array $data): static;

    /** @return Option\Option<static> */
    #[Override]
    final public static function from(Message $message): Option\Option
    {
        if (!$message instanceof JsonMessage || ($message->data['cmd'] ?? null) !== 'moveToArea') {
            return Option\none();
        }

        return Option\some(static::read($message->data));
    }
}
