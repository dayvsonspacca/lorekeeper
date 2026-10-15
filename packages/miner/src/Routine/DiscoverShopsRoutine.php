<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Miner\Discovery\ShopQueue;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Protocol\Command\JoinMapCommand;
use Lorekeeper\Protocol\Command\LoadShopCommand;
use Lorekeeper\Protocol\Command\LogoutCommand;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\DateTime\Duration;
use Psl\Option;

final readonly class DiscoverShopsRoutine
{
    /** @param Closure(string): void $log */
    public function __construct(
        private GameEntry $entry,
        private ShopQueue $queue,
        private Closure $log,
    ) {}

    /**
     * @param int<1, max> $limit
     *
     * @throws SessionNotReadyException
     */
    public function run(Duration $interval, int $limit, CancellationTokenInterface $stop): void
    {
        $this->entry->enter();
        $visited = 0;

        try {
            foreach (array_slice(
                $this->queue->toLoad(),
                offset: 0,
                length: $limit,
                preserve_keys: true,
            ) as $map => $shops) {
                Async\sleep($interval, $stop);
                $visited++;
                $this->visit(new MapName($map), $shops, $interval, $stop);
            }
        } catch (CancelledException) {
            ($this->log)('> stop requested');
        }

        Async\sleep($interval);
        ($this->log)("> visited {$visited} maps");
        $this->entry->connection->send(new LogoutCommand());
        ($this->log)('> logout');
    }

    /** @param non-empty-list<ShopId> $shops */
    private function visit(MapName $map, array $shops, Duration $interval, CancellationTokenInterface $stop): void
    {
        $this->entry->connection->send(new JoinMapCommand($this->entry->login->player, $map, Option\none()));
        ($this->log)("> join {$map}");

        try {
            $areaId = $this->entry->arrivedAt($map);
        } catch (SessionNotReadyException) {
            ($this->log)("! {$map}: could not join");

            return;
        }

        foreach ($shops as $shop) {
            Async\sleep($interval, $stop);
            $this->entry->connection->send(new LoadShopCommand($areaId, $shop));
            ($this->log)("> {$map}: shop {$shop}");
        }
    }
}
