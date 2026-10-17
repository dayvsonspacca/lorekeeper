<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

final readonly class PlayerDiedEvent implements Event
{
    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        return $message instanceof JsonMessage && ($message->data['cmd'] ?? null) === 'playerDeath'
            ? Option\some(new static())
            : Option\none();
    }
}
