<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event;

use Lorekeeper\Protocol\Message\DelimitedMessage;
use Lorekeeper\Protocol\Message\Message;
use Psl\Option;

final readonly class Notice
{
    /** @return Option\Option<string> */
    public static function text(Message $message, string $channel): Option\Option
    {
        if (!$message instanceof DelimitedMessage || ($message->data[1] ?? null) !== $channel) {
            return Option\none();
        }

        return Option\from_nullable($message->data[3] ?? null);
    }
}
