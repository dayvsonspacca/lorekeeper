<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

final readonly class AttackRejectedEvent implements Event
{
    public function __construct(
        public ?int $action,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        if (
            !$message instanceof JsonMessage
            || ($message->data['cmd'] ?? null) !== 'sars'
            || ($message->data['iRes'] ?? null) !== 0
        ) {
            return Option\none();
        }

        return Option\some(new static(is_int($message->data['actID'] ?? null) ? $message->data['actID'] : null));
    }
}
