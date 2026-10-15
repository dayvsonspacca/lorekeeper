<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopUnavailable;
use Lorekeeper\Miner\Exception\PeerClosedException;
use Lorekeeper\Miner\Exception\SessionNotReadyException;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Session;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Command\JoinMapCommand;
use Lorekeeper\Protocol\Command\LoadQuestCommand;
use Lorekeeper\Protocol\Command\LoadShopCommand;
use Psl\Async;
use Psl\Async\CancellationTokenInterface;
use Psl\DateTime\Duration;
use Psl\Option;

final readonly class MapVisit
{
    private const int QUEST_BATCH = 10;

    /** @param Closure(string): void $log */
    public function __construct(
        private GameEntry $entry,
        private Recorder $recorder,
        private Closure $log,
    ) {}

    /** @throws PeerClosedException */
    public function join(MapName $map): ?AreaId
    {
        $this->entry->connection->send(new JoinMapCommand($this->entry->login->player, $map, Option\none()));
        ($this->log)("> join {$map}");

        try {
            return $this->entry->arrivedAt($map);
        } catch (SessionNotReadyException) {
            ($this->log)("! {$map}: could not join");

            return null;
        }
    }

    /**
     * @param array<string, non-empty-list<QuestId>> $npcs
     *
     * @throws PeerClosedException
     */
    public function askQuests(
        MapName $map,
        AreaId $area,
        array $npcs,
        Duration $interval,
        CancellationTokenInterface $stop,
    ): void {
        foreach ($npcs as $npc => $quests) {
            foreach (array_chunk($quests, self::QUEST_BATCH) as $batch) {
                Async\sleep($interval, $stop);
                $this->entry->connection->send(
                    new LoadQuestCommand($area, $batch[0], ...array_slice($batch, offset: 1)),
                );
                ($this->log)("> {$map} / {$npc}: " . count($batch) . ' quests');
            }
        }
    }

    /**
     * @param list<ShopId> $shops
     *
     * @throws PeerClosedException
     */
    public function openShops(
        MapName $map,
        AreaId $area,
        array $shops,
        Duration $interval,
        CancellationTokenInterface $stop,
    ): void {
        foreach ($shops as $shop) {
            Async\sleep($interval, $stop);
            $this->entry->connection->send(new LoadShopCommand($area, $shop));
            ($this->log)("> {$map}: shop {$shop}");

            if ($this->entry->answered(static fn(Session $session): bool => $session->hasLoaded($shop), $interval)) {
                continue;
            }

            $this->recorder->record([new Fact($shop, new ShopUnavailable())], Source::Socket);
            ($this->log)("! {$map}: shop {$shop} did not answer");
        }
    }
}
