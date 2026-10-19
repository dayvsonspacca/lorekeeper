<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Game;

use Illuminate\Contracts\Cache\Repository;
use Lorekeeper\Client\Api\Server\ServerListClient;
use Lorekeeper\Client\Api\TravelMap\TravelMapClient;
use Lorekeeper\Server\Server;
use RuntimeException;

final readonly class GameCatalog
{
    public function __construct(
        private ServerListClient $servers,
        private TravelMapClient $travelMap,
        private Repository $cache,
    ) {}

    /** @return array<string, string> */
    public function servers(): array
    {
        return $this->remember('catalog.servers', 3600, function (): array {
            $servers = [];
            foreach ($this->servers->fetch() as $server) {
                $servers[$server->name] = self::label($server);
            }

            return $servers;
        });
    }

    /** @return array<string, string> */
    public function maps(): array
    {
        return $this->remember('catalog.maps', 86_400, function (): array {
            $maps = [];
            foreach ($this->travelMap->fetch()->regions as $region) {
                foreach ($region->maps as $entry) {
                    $name = $entry->map->mapName->value;
                    $maps[$name] ??= "{$entry->map->title->value} ({$name})";
                }
            }

            ksort($maps);

            return $maps;
        });
    }

    private static function label(Server $server): string
    {
        return (
            $server->name . match (true) {
                !$server->online => ' (offline)',
                $server->memberOnly => ' (members)',
                default => '',
            }
        );
    }

    /**
     * @param callable(): array<string, string> $fetch
     *
     * @return array<string, string>
     */
    private function remember(string $key, int $seconds, callable $fetch): array
    {
        /** @var null|array<string, string> $cached */
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $fresh = $fetch();
        } catch (RuntimeException) {
            return [];
        }

        $this->cache->put($key, $fresh, $seconds);

        return $fresh;
    }
}
