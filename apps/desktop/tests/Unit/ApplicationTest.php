<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private const string WINDOWS_PACKAGES_CACHE = 'C:\Users\hero\AppData\Roaming\lorekeeper/bootstrap/cache/packages.php';

    #[Test]
    public function it_takes_a_windows_cache_path_as_absolute(): void
    {
        $_SERVER['APP_PACKAGES_CACHE'] = self::WINDOWS_PACKAGES_CACHE;

        try {
            $application = new Application('/home/hero/lorekeeper');

            static::assertSame(self::WINDOWS_PACKAGES_CACHE, $application->getCachedPackagesPath());
        } finally {
            unset($_SERVER['APP_PACKAGES_CACHE']);
        }
    }

    #[Test]
    public function it_still_places_a_relative_cache_path_under_the_base_path(): void
    {
        $_SERVER['APP_PACKAGES_CACHE'] = 'cache/packages.php';

        try {
            $application = new Application('/home/hero/lorekeeper');

            static::assertSame('/home/hero/lorekeeper/cache/packages.php', $application->getCachedPackagesPath());
        } finally {
            unset($_SERVER['APP_PACKAGES_CACHE']);
        }
    }
}
