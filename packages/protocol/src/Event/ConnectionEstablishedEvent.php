<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event;

use Lorekeeper\Protocol\Message\Message;
use Lorekeeper\Protocol\Message\XmlMessage;
use Override;
use Psl\Option;

final readonly class ConnectionEstablishedEvent implements Event
{
    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof XmlMessage || $message->dom->documentElement?->nodeName !== 'cross-domain-policy') {
            return Option\none();
        }

        return Option\some(new static());
    }
}
