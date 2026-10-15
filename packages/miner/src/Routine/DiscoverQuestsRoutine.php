<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Miner\Discovery\QuestQueue;
use Lorekeeper\Miner\Exception\PeerClosedException;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Protocol\Command\LogoutCommand;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\DateTime\Duration;

final readonly class DiscoverQuestsRoutine
{
    /** @param Closure(string): void $log */
    public function __construct(
        private GameEntry $entry,
        private QuestQueue $queue,
        private MapVisit $visit,
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
            ) as $map => $npcs) {
                Async\sleep($interval, $stop);
                $visited++;
                $this->explore(new MapName($map), $npcs, $interval, $stop);
            }
        } catch (CancelledException) {
            ($this->log)('> stop requested');
        } catch (PeerClosedException $exception) {
            ($this->log)("! {$exception->getMessage()}");
            ($this->log)("> visited {$visited} maps");

            return;
        }

        Async\sleep($interval);
        ($this->log)("> visited {$visited} maps");
        $this->entry->connection->send(new LogoutCommand());
        ($this->log)('> logout');
    }

    /**
     * @param array<string, non-empty-list<QuestId>> $npcs
     *
     * @throws PeerClosedException
     */
    private function explore(MapName $map, array $npcs, Duration $interval, CancellationTokenInterface $stop): void
    {
        $area = $this->visit->join($map);
        if ($area !== null) {
            $this->visit->askQuests($map, $area, $npcs, $interval, $stop);
        }
    }
}
