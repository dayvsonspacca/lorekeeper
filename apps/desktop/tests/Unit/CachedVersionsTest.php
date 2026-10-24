<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Lorekeeper\Apps\Desktop\Bots\CachedVersions;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Client\Session\ClientVersion;
use Lorekeeper\Client\Session\Login;
use Lorekeeper\Client\Session\Versions;
use PHPUnit\Framework\Attributes\Test;

final class CachedVersionsTest extends TestCase
{
    #[Test]
    public function the_bots_start_from_the_version_the_app_knows(): void
    {
        static::assertEquals(new ClientVersion(Login::VERSION), new CachedVersions()->current());
        static::assertInstanceOf(CachedVersions::class, $this->app?->make(Versions::class));
    }

    #[Test]
    public function every_bot_remembers_the_version_one_of_them_learned(): void
    {
        new CachedVersions()->learn(new ClientVersion('5.4'));

        static::assertEquals(new ClientVersion('5.4'), new CachedVersions()->current());
    }

    #[Test]
    public function a_blank_memory_falls_back_to_the_known_version(): void
    {
        Cache::forever('lorekeeper.client.version', '');

        static::assertEquals(new ClientVersion(Login::VERSION), new CachedVersions()->current());
    }
}
