<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Protocol\Command\LogoutCommand;
use Psl\Async;
use Psl\DateTime\Duration;

final readonly class ListenRoutine
{
    /** @param Closure(string): void $log */
    public function __construct(
        private GameEntry $entry,
        private Closure $log,
    ) {}

    /** @throws SessionNotReadyException */
    public function run(Duration $listenFor): void
    {
        $this->entry->enter();
        ($this->log)("> listening for {$listenFor->getTotalSeconds()}s");

        Async\sleep($listenFor);

        $this->entry->connection->send(new LogoutCommand());
        ($this->log)('> logout');
    }
}
