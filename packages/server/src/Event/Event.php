<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\Message;
use Psl\Option\Option;

interface Event
{
    /**
     * @throws MalformedEventException
     *
     * @return Option<static>
     */
    public static function from(Message $message): Option;

    public function name(): string;

    public function summary(): string;
}
