<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

use Lorekeeper\Client\Bot\Task\Task;

/**
 * A task handed to a session, with the id the app knows it by.
 */
final readonly class Job
{
    public function __construct(
        public int|string $id,
        public Task $task,
    ) {}
}
