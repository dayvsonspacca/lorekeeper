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
 * The server refused to log our player in, and may have said why.
 */
final readonly class LoginFailedEvent implements Event
{
    use NamedAfterClass;

    public function __construct(
        public string $reason = '',
    ) {}

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

        $said = array_slice($message->data, offset: 5);

        return Option\some(new static(trim((string) end($said))));
    }

    #[Override]
    public function summary(): string
    {
        return $this->reason === '' ? 'Login refused' : "Login refused: {$this->reason}";
    }
}
