<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

final readonly class BootstrapCache
{
    private const array FILES = [
        'APP_SERVICES_CACHE' => 'services.php',
        'APP_PACKAGES_CACHE' => 'packages.php',
        'APP_CONFIG_CACHE' => 'config.php',
        'APP_ROUTES_CACHE' => 'routes-v7.php',
        'APP_EVENTS_CACHE' => 'events.php',
    ];

    /** @param array<string, string> $env */
    public static function folder(array $env): ?string
    {
        $userData = $env['NATIVEPHP_USER_DATA_PATH'] ?? '';
        if (
            ($env['NATIVEPHP_RUNNING'] ?? null) !== 'true'
            || ($env['APP_DEBUG'] ?? null) !== 'false'
            || $userData === ''
        ) {
            return null;
        }

        return "{$userData}/bootstrap/cache";
    }

    /**
     * @param array<string, string> $env
     *
     * @return array<string, string>
     */
    public static function paths(array $env): array
    {
        $folder = self::folder($env);
        if ($folder === null || array_key_exists('APP_CONFIG_CACHE', $env)) {
            return [];
        }

        return array_map(static fn(string $file): string => "{$folder}/{$file}", self::FILES);
    }

    public static function redirect(): void
    {
        $folder = self::folder(getenv());
        if ($folder !== null && !is_dir($folder)) {
            mkdir($folder, permissions: 0o755, recursive: true);
        }

        foreach (self::paths(getenv()) as $key => $path) {
            putenv("{$key}={$path}");
            $_ENV[$key] = $path;
            $_SERVER[$key] = $path;
        }
    }
}
