<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native;

use Illuminate\Support\Str;

final readonly class Downloads
{
    public static function folder(): string
    {
        $configured = config('desktop.downloads');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $home = (string) getenv('HOME');
        $named = self::named($home);
        if ($named !== null && is_dir($named)) {
            return $named;
        }

        return is_dir("{$home}/Downloads") ? "{$home}/Downloads" : $home;
    }

    public static function save(string $title, string $contents): string
    {
        $folder = self::folder();
        $base = Str::slug($title) === '' ? 'script' : Str::slug($title);
        $path = "{$folder}/{$base}.json";
        for ($copy = 2; file_exists($path); $copy++) {
            $path = "{$folder}/{$base}-{$copy}.json";
        }

        file_put_contents($path, $contents);

        return $path;
    }

    private static function named(string $home): ?string
    {
        $config = (string) getenv('XDG_CONFIG_HOME');
        $dirs = ($config === '' ? "{$home}/.config" : $config) . '/user-dirs.dirs';
        if (!is_file($dirs)) {
            return null;
        }

        $found = preg_match('/^XDG_DOWNLOAD_DIR="(.+)"$/m', (string) file_get_contents($dirs), $match);

        return $found === 1 ? str_replace('$HOME', $home, $match[1]) : null;
    }
}
