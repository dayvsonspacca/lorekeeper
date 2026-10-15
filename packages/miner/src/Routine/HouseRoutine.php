<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Protocol\Command\JoinHouseCommand;
use Lorekeeper\Protocol\Command\LogoutCommand;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\DateTime\Duration;

final readonly class HouseRoutine
{
    /** @param Closure(string): void $log */
    public function __construct(
        private GameEntry $entry,
        private Closure $log,
    ) {}

    /** @throws SessionNotReadyException */
    public function run(CancellationTokenInterface $stop): void
    {
        $this->entry->enter();

        Async\sleep(Duration::seconds(2));
        $this->entry->connection->send(new JoinHouseCommand($this->entry->login->player));
        ($this->log)("> join house of {$this->entry->login->player}, listening until stopped");

        try {
            while (true) {
                Async\sleep(Duration::hours(1), $stop);
            }
        } catch (CancelledException) {
            ($this->log)('> stop requested');
        }

        $this->entry->connection->send(new LogoutCommand());
        ($this->log)('> logout');
    }
}
