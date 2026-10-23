<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

/**
 * Our player came back to life after dying.
 */
final readonly class ResurrectedEvent implements Event
{
    use NamedAfterClass;

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        return $message instanceof DelimitedMessage && ($message->data[1] ?? null) === 'resTimed'
            ? Option\some(new static())
            : Option\none();
    }

    #[Override]
    public function summary(): string
    {
        return 'Back to life';
    }
}
