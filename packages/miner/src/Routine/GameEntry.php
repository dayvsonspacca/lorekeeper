<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Miner\Connection;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Miner\Login\GameLogin;
use Lorekeeper\Miner\Session;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Command\JoinInitialMapCommand;
use Lorekeeper\Protocol\Command\LoadPlayerInventoryCommand;
use Lorekeeper\Protocol\Command\LoginCommand;
use Lorekeeper\Protocol\Command\PolicyFileRequestCommand;
use Lorekeeper\Protocol\SocketId;
use Psl\Async;
use Psl\Comparison\Order;
use Psl\DateTime\Duration;
use Psl\Option\Option;

final readonly class GameEntry
{
    /** @param Closure(string): void $log */
    public function __construct(
        public Connection $connection,
        public GameLogin $login,
        private Session $session,
        private Closure $log,
        private ?Duration $timeout = null,
    ) {}

    /** @throws SessionNotReadyException */
    public function enter(): void
    {
        $this->connection->send(new PolicyFileRequestCommand());
        $this->connection->send(new LoginCommand($this->login->player, $this->login->token, $this->login->version));
        ($this->log)('> login');
        $socketId = $this->await('logged in', fn(): Option => $this->session->socketId());

        $this->connection->send(new JoinInitialMapCommand());
        ($this->log)('> join initial map');
        $areaId = $this->await('in a map', fn(): Option => $this->session->areaId());

        $this->connection->send(new LoadPlayerInventoryCommand($areaId, $socketId));
        ($this->log)('> load inventory');
    }

    /** @throws SessionNotReadyException */
    public function arrivedAt(MapName $map): AreaId
    {
        return $this->await("in {$map}", fn(): Option => $this->session
            ->mapName()
            ->filter(static fn(MapName $current): bool => $current->value === $map->value)
            ->and($this->session->areaId()));
    }

    /**
     * @template T of SocketId|AreaId
     *
     * @param Closure(): Option<T> $probe
     *
     * @return T
     */
    private function await(string $state, Closure $probe): SocketId|AreaId
    {
        $timeout = $this->timeout ?? Duration::seconds(15);
        $waited = Duration::zero();
        $step = Duration::milliseconds(100);

        while ($probe()->isNone()) {
            if ($waited->compare($timeout) !== Order::Less) {
                throw new SessionNotReadyException("Session not {$state} after {$timeout->getTotalSeconds()}s.");
            }

            Async\sleep($step);
            $waited = $waited->plus($step);
        }

        return $probe()->unwrap();
    }
}
