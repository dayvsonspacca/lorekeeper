<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Message;

use Psl\Option\Option;

interface Message
{
    public string $raw { get; }

    /** @return Option<static> */
    public static function from(string $message): Option;
}
