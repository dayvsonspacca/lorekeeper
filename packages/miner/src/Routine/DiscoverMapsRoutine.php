<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Miner\Discovery\AnalysisFacts;
use Lorekeeper\Miner\Discovery\MapDiscoverer;
use Lorekeeper\Miner\Discovery\MapQueue;
use Lorekeeper\Miner\Exception\DiscoveryFailedException;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Protocol\Command\JoinMapCommand;
use Lorekeeper\Protocol\Command\LogoutCommand;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\DateTime\Duration;
use Psl\Option;

final class DiscoverMapsRoutine
{
    /** @var array<string, true> */
    private array $attempted = [];

    /** @var array<string, true> */
    private array $analyzed = [];

    /** @param Closure(string): void $log */
    public function __construct(
        private readonly GameEntry $entry,
        private readonly MapQueue $queue,
        private readonly MapDiscoverer $discoverer,
        private readonly Recorder $recorder,
        private readonly Closure $log,
    ) {}

    /**
     * @param int<1, max> $limit
     *
     * @throws SessionNotReadyException
     */
    public function run(Duration $interval, int $limit, CancellationTokenInterface $stop): void
    {
        $this->entry->enter();

        try {
            while (true) {
                $this->analyzePending();

                $next = array_values(array_filter(
                    $this->queue->toVisit(),
                    fn(MapName $map): bool => !array_key_exists($map->value, $this->attempted),
                ));

                if ($next === [] || count($this->attempted) >= $limit) {
                    break;
                }

                foreach (array_slice($next, offset: 0, length: $limit - count($this->attempted)) as $map) {
                    $this->visit($map, $interval, $stop);
                }
            }
        } catch (CancelledException) {
            ($this->log)('> stop requested');
        }

        ($this->log)('> visited ' . count($this->attempted) . ' maps, analyzed ' . count($this->analyzed));
        $this->entry->connection->send(new LogoutCommand());
        ($this->log)('> logout');
    }

    private function visit(MapName $map, Duration $interval, CancellationTokenInterface $stop): void
    {
        $this->attempted[$map->value] = true;
        $this->entry->connection->send(new JoinMapCommand($this->entry->login->player, $map, Option\none()));
        ($this->log)("> join {$map}");

        Async\sleep($interval, $stop);
    }

    private function analyzePending(): void
    {
        foreach ($this->queue->toAnalyze() as $name => $gamefile) {
            if (array_key_exists($name, $this->analyzed)) {
                continue;
            }

            $this->analyzed[$name] = true;
            $map = new MapName($name);

            try {
                $analysis = $this->discoverer->discover($gamefile);
            } catch (DiscoveryFailedException $exception) {
                ($this->log)("! {$name}: {$exception->getMessage()}");

                continue;
            }

            $this->recorder->record([
                ...AnalysisFacts::of($map, $analysis),
                new Fact($map, new MapAnalyzed($gamefile)),
            ], Source::Swf);
            ($this->log)("< {$name}: " . count($analysis->links) . ' links, ' . count($analysis->npcs) . ' npcs');
        }
    }
}
