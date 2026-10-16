<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Lorekeeper\Apps\Wiki\Game\GameCatalog;
use Lorekeeper\Client\Login\ServerListClient;
use Lorekeeper\Client\TravelMap\TravelMapClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GameCatalogTest extends TestCase
{
    #[Test]
    public function it_lists_servers_and_maps_from_the_game(): void
    {
        $catalog = self::catalog(__DIR__ . '/../Fixtures/servers.json', __DIR__ . '/../Fixtures/travelmap.json');

        static::assertSame(
            ['Twilly' => 'Twilly', 'Sir Ver' => 'Sir Ver (members)', 'Espada' => 'Espada'],
            $catalog->servers(),
        );
        static::assertSame(['battleon' => 'Battleon (battleon)', 'forest' => 'Forest (forest)'], $catalog->maps());
    }

    #[Test]
    public function it_remembers_what_it_fetched_and_tries_again_after_a_failure(): void
    {
        $cache = new Repository(new ArrayStore());
        $missing = __DIR__ . '/../Fixtures/missing.json';

        static::assertSame([], self::catalog($missing, $missing, $cache)->servers());
        static::assertFalse($cache->has('catalog.servers'));

        self::catalog(__DIR__ . '/../Fixtures/servers.json', $missing, $cache)->servers();

        static::assertCount(3, self::catalog($missing, $missing, $cache)->servers());
    }

    private static function catalog(string $servers, string $travelMap, ?Repository $cache = null): GameCatalog
    {
        return new GameCatalog(
            new ServerListClient("file://{$servers}"),
            new TravelMapClient("file://{$travelMap}"),
            $cache ?? new Repository(new ArrayStore()),
        );
    }
}
