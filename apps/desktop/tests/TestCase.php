<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Game\GameCatalog;
use Lorekeeper\Client\Api\Server\ServerListClient;
use Lorekeeper\Client\Api\TravelMap\TravelMapClient;
use Override;

abstract class TestCase extends BaseTestCase
{
    protected FakeLauncher $launcher;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['desktop.timezone' => 'UTC']);

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
