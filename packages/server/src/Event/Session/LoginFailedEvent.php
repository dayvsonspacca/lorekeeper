<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Session;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

/**
 * The server refused to log our player in.
 */
final readonly class LoginFailedEvent implements Event
{
    use NamedAfterClass;

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (!$message instanceof DelimitedMessage || ($message->data[1] ?? null) !== 'loginResponse') {
            return Option\none();
        }

        if (!in_array($message->data[3] ?? null, ['0', 'false'], strict: true)) {
            return Option\none();
        }

        return Option\some(new static());
    }

    #[Override]
    public function summary(): string
    {
        return 'Login refused';
    }
}
