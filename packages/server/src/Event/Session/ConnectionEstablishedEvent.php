<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Session;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Message\Message;
use Lorekeeper\Server\Message\XmlMessage;
use Override;
use Psl\Option;

/**
 * The connection to the game server is open.
 */
final readonly class ConnectionEstablishedEvent implements Event
{
    use NamedAfterClass;

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof XmlMessage || $message->dom->documentElement?->nodeName !== 'cross-domain-policy') {
            return Option\none();
        }

        return Option\some(new static());
    }

    #[Override]
    public function summary(): string
    {
        return 'Connection established';
    }
}
