<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Miner\Discovery\AnalysisFacts;
use Lorekeeper\Miner\Discovery\MapAnalysis;
use Lorekeeper\Miner\Discovery\MapDiscoverer;
use Lorekeeper\Miner\Exception\DiscoveryFailedException;
use Lorekeeper\Miner\Exception\PeerClosedException;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Protocol\Command\LogoutCommand;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\Exception\CancelledException;
use Psl\DateTime\Duration;

final readonly class ExploreMapRoutine
{
    /** @param Closure(string): void $log */
    public function __construct(
        private GameEntry $entry,
        private MapVisit $visit,
        private MapDiscoverer $discoverer,
        private Recorder $recorder,
        private Closure $log,
    ) {}

    /** @throws SessionNotReadyException */
    public function run(MapName $map, Duration $interval, CancellationTokenInterface $stop): void
    {
        $this->entry->enter();

        try {
            Async\sleep($interval, $stop);
            $this->explore($map, $interval, $stop);
        } catch (CancelledException) {
            ($this->log)('> stop requested');
        } catch (PeerClosedException $exception) {
            ($this->log)("! {$exception->getMessage()}");

            return;
        }

        Async\sleep($interval);
        $this->entry->connection->send(new LogoutCommand());
        ($this->log)('> logout');
    }

    /** @throws PeerClosedException */
    private function explore(MapName $map, Duration $interval, CancellationTokenInterface $stop): void
    {
        $area = $this->visit->join($map);
        if ($area === null) {
            return;
        }

        $file = $this->entry->mapFile();
        if ($file->isNone()) {
            ($this->log)("! {$map}: the server sent no map file");

            return;
        }

        $analysis = $this->analyze($map, $file->unwrap());
        if ($analysis === null) {
            return;
        }

        $quests = [];
        $shops = [];
        foreach ($analysis->npcs as $npc) {
            if ($npc->buttons->quests !== []) {
                $quests[$npc->popup->value] = $npc->buttons->quests;
            }

            foreach ($npc->buttons->shops as $shop) {
                $shops[$shop->value] = $shop;
            }
        }

        $this->visit->askQuests($map, $area, $quests, $interval, $stop);
        $this->visit->openShops($map, $area, array_values($shops), $interval, $stop);
        ($this->log)("> {$map}: done, " . count($quests) . ' npcs with quests, ' . count($shops) . ' shops');
    }

    private function analyze(MapName $map, Gamefile $file): ?MapAnalysis
    {
        try {
            $analysis = $this->discoverer->discover($file);
        } catch (DiscoveryFailedException $exception) {
            ($this->log)("! {$map}: {$exception->getMessage()}");

            return null;
        }

        $this->recorder->record([
            ...AnalysisFacts::of($map, $analysis),
            new Fact($map, new MapAnalyzed($file)),
        ], Source::Swf);
        ($this->log)(
            "< {$map}: "
            . count($analysis->exits)
            . ' exits, '
            . count($analysis->mentions)
            . ' mentions, '
            . count($analysis->npcs)
            . ' npcs',
        );

        return $analysis;
    }
}
