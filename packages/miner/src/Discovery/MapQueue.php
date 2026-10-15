<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Closure;
use DateTimeImmutable;
use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapLink;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapUnavailable;
use Lorekeeper\Persistence\Store\FactStore;
use Lorekeeper\Persistence\Store\StoredFact;
use Psl\DateTime\Duration;

final readonly class MapQueue
{
    private const string HOUSE = 'house';

    /** @param Closure(): DateTimeImmutable $clock */
    public function __construct(
        private FactStore $store,
        private Closure $clock,
        private Duration $retryUnavailableAfter,
    ) {}

    /** @return array<string, Gamefile> */
    public function toAnalyze(): array
    {
        $analyzed = self::mapNames($this->store->withValue(MapLink::class));
        $pending = [];

        foreach ($this->store->withValue(Gamefile::class) as $stored) {
            $subject = $stored->fact->subject;
            if (!$subject instanceof MapName || array_key_exists($subject->value, $analyzed)) {
                continue;
            }

            $pending[$subject->value] = $stored->fact->value instanceof Gamefile
                ? $stored->fact->value
                : throw new \LogicException();
        }

        return $pending;
    }

    /** @return list<MapName> */
    public function toVisit(): array
    {
        $known = [...self::mapNames($this->store->withValue(MapId::class)), ...$this->linkedMaps()];
        $skip = [
            ...self::mapNames(array_filter(
                $this->store->withValue(Gamefile::class),
                static fn(StoredFact $stored): bool => $stored->fact->subject instanceof MapName,
            )),
            ...self::mapNames($this->store->withValue(MemberOnly::class)),
            ...$this->recentlyUnavailable(),
            self::HOUSE => true,
        ];

        return array_values(array_map(
            static fn(string $name): MapName => new MapName($name),
            array_keys(array_diff_key($known, $skip)),
        ));
    }

    /** @return array<string, true> */
    private function linkedMaps(): array
    {
        $names = [];

        foreach ($this->store->withValue(MapLink::class) as $stored) {
            if (!$stored->fact->value instanceof MapLink) {
                continue;
            }

            $names[$stored->fact->value->mapName->value] = true;
        }

        return $names;
    }

    /** @return array<string, true> */
    private function recentlyUnavailable(): array
    {
        $since = ($this->clock)()->getTimestamp() - (int) $this->retryUnavailableAfter->getTotalSeconds();

        return self::mapNames(array_filter(
            $this->store->withValue(MapUnavailable::class),
            static fn(StoredFact $stored): bool => $stored->lastSeen->getTimestamp() >= $since,
        ));
    }

    /**
     * @param array<array-key, StoredFact> $facts
     *
     * @return array<string, true>
     */
    private static function mapNames(array $facts): array
    {
        $names = [];

        foreach ($facts as $stored) {
            if (!$stored->fact->subject instanceof MapName) {
                continue;
            }

            $names[$stored->fact->subject->value] = true;
        }

        return $names;
    }
}
