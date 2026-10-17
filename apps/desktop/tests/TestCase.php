<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Game\GameCatalog;
use Lorekeeper\Client\Login\ServerListClient;
use Lorekeeper\Client\TravelMap\TravelMapClient;
use Override;

abstract class TestCase extends BaseTestCase
{
    protected FakeLauncher $launcher;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->launcher = new FakeLauncher();
        $this->app?->instance(Launcher::class, $this->launcher);

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
