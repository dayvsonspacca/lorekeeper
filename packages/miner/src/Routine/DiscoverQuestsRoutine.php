<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Miner\Discovery\QuestQueue;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Protocol\Command\JoinMapCommand;
use Lorekeeper\Protocol\Command\LoadQuestCommand;
use Lorekeeper\Protocol\Command\LogoutCommand;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\DateTime\Duration;
use Psl\Option;

final readonly class DiscoverQuestsRoutine
{
    private const int BATCH = 10;

    /** @param Closure(string): void $log */
    public function __construct(
        private GameEntry $entry,
        private QuestQueue $queue,
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
                $this->visit(new MapName($map), $npcs, $interval, $stop);
            }
        } catch (CancelledException) {
            ($this->log)('> stop requested');
        }

        Async\sleep($interval);
        ($this->log)("> visited {$visited} maps");
        $this->entry->connection->send(new LogoutCommand());
        ($this->log)('> logout');
    }

    /** @param array<string, non-empty-list<QuestId>> $npcs */
    private function visit(MapName $map, array $npcs, Duration $interval, CancellationTokenInterface $stop): void
    {
        $this->entry->connection->send(new JoinMapCommand($this->entry->login->player, $map, Option\none()));
        ($this->log)("> join {$map}");

        try {
            $areaId = $this->entry->arrivedAt($map);
        } catch (SessionNotReadyException) {
            ($this->log)("! {$map}: could not join");

            return;
        }

        foreach ($npcs as $npc => $quests) {
            foreach (array_chunk($quests, self::BATCH) as $batch) {
                Async\sleep($interval, $stop);
                $this->entry->connection->send(
                    new LoadQuestCommand($areaId, $batch[0], ...array_slice($batch, offset: 1)),
                );
                ($this->log)("> {$map} / {$npc}: " . count($batch) . ' quests');
            }
        }
    }
}
