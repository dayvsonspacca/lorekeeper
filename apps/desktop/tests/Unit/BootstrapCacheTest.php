<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Native\BootstrapCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BootstrapCacheTest extends TestCase
{
    private const array PACKAGED = [
        'LOREKEEPER_SHELL' => 'tauri',
        'APP_DEBUG' => 'false',
        'LOREKEEPER_DATA_PATH' => '/home/hero/.local/share/lorekeeper',
    ];

    #[Test]
    public function the_packaged_app_keeps_its_caches_in_the_user_data_folder(): void
    {
        static::assertSame(
            '/home/hero/.local/share/lorekeeper/bootstrap/cache',
            BootstrapCache::folder(self::PACKAGED),
        );
        static::assertSame(
            [
                'APP_SERVICES_CACHE' => '/home/hero/.local/share/lorekeeper/bootstrap/cache/services.php',
                'APP_PACKAGES_CACHE' => '/home/hero/.local/share/lorekeeper/bootstrap/cache/packages.php',
                'APP_CONFIG_CACHE' => '/home/hero/.local/share/lorekeeper/bootstrap/cache/config.php',
                'APP_ROUTES_CACHE' => '/home/hero/.local/share/lorekeeper/bootstrap/cache/routes-v7.php',
                'APP_EVENTS_CACHE' => '/home/hero/.local/share/lorekeeper/bootstrap/cache/events.php',
            ],
            BootstrapCache::paths(self::PACKAGED),
        );
    }

    #[Test]
    public function it_leaves_the_caches_alone_outside_the_packaged_app(): void
    {
        static::assertNull(BootstrapCache::folder([]));
        static::assertNull(BootstrapCache::folder([...self::PACKAGED, 'APP_DEBUG' => 'true']));
        static::assertNull(BootstrapCache::folder([...self::PACKAGED, 'LOREKEEPER_DATA_PATH' => '']));
        static::assertNull(BootstrapCache::folder([...self::PACKAGED, 'LOREKEEPER_SHELL' => 'electron']));
        static::assertSame([], BootstrapCache::paths([...self::PACKAGED, 'APP_DEBUG' => 'true']));
    }

    #[Test]
    public function it_keeps_the_laravel_caches_where_they_were_already_placed(): void
    {
        static::assertSame(
            [],
            BootstrapCache::paths([...self::PACKAGED, 'APP_CONFIG_CACHE' => '/elsewhere/config.php']),
        );
    }
}
