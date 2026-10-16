<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

abstract readonly class NoticeEvent implements Event
{
    final public function __construct() {}

    abstract protected static function channel(): string;

    abstract protected static function phrase(): string;

    /** @return Option\Option<static> */
    #[Override]
    final public static function from(Message $message): Option\Option
    {
        return Notice::text($message, static::channel())->filter(static fn(string $text): bool => str_contains(
            strtolower($text),
            static::phrase(),
        ))->map(static fn(): static => new static());
    }
}
