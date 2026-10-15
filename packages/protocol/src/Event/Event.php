<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event;

use Lorekeeper\Protocol\Exception\MalformedEventException;
use Lorekeeper\Protocol\Message\Message;
use Psl\Option\Option;

interface Event
{
    /**
     * @throws MalformedEventException
     *
     * @return Option<static>
     */
    public static function from(Message $message): Option;
}
