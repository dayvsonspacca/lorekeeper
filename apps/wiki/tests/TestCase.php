<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Lorekeeper\Apps\Wiki\Game\GameCatalog;
use Lorekeeper\Client\Login\ServerListClient;
use Lorekeeper\Client\TravelMap\TravelMapClient;
use Override;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app?->bind(
            GameCatalog::class,
            static fn(): GameCatalog => new GameCatalog(
                new ServerListClient('file://' . __DIR__ . '/Fixtures/servers.json'),
                new TravelMapClient('file://' . __DIR__ . '/Fixtures/travelmap.json'),
                cache()->store('array'),
            ),
        );
    }
}
