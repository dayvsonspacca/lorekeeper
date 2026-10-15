<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Closure;
use DateTimeImmutable;
use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapMention;
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
        $analyzed = [];
        foreach ($this->store->withValue(MapAnalyzed::class) as $stored) {
            if (!$stored->fact->value instanceof MapAnalyzed) {
                continue;
            }

            $analyzed[(string) $stored->fact->subject . '|' . $stored->fact->value->gamefile->value] = true;
        }

        $pending = [];
        foreach ($this->store->withValue(Gamefile::class) as $stored) {
            $subject = $stored->fact->subject;
            $gamefile = $stored->fact->value;
            if (
                !$subject instanceof MapName
                || !$gamefile instanceof Gamefile
                || array_key_exists($subject->value . '|' . $gamefile->value, $analyzed)
            ) {
                continue;
            }

            $pending[$subject->value] = $gamefile;
        }

        return $pending;
    }

    /** @return list<MapName> */
    public function toVisit(): array
    {
        $known = [
            ...$this->targets(MapExit::class),
            ...self::mapNames($this->store->withValue(MapId::class)),
            ...$this->targets(MapMention::class),
        ];
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

    /**
     * @param class-string<MapExit|MapMention> $valueClass
     *
     * @return array<string, true>
     */
    private function targets(string $valueClass): array
    {
        $names = [];

        foreach ($this->store->withValue($valueClass) as $stored) {
            $value = $stored->fact->value;
            if (!$value instanceof MapExit && !$value instanceof MapMention) {
                continue;
            }

            $names[$value->mapName->value] = true;
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
