<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Map\MapName;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class MapNameExtractor
{
    private const array PATTERNS = [
        '/gotoTown\("([^"]+)"/',
        '/strNewMap\s*=\s*"([^"]+)"/',
        '/strString\s*=\s*"([^"]+)"/',
    ];

    private const string VALID = '/^[a-z][a-z0-9]+$/';

    /** @return list<MapName> */
    public static function fromSource(string $code): array
    {
        $names = [];

        foreach (self::PATTERNS as $pattern) {
            $matches = [];
            preg_match_all($pattern, $code, $matches);

            foreach ($matches[1] as $candidate) {
                $name = strtolower($candidate);
                if (preg_match(self::VALID, $name) === 1) {
                    $names[$name] = new MapName($name);
                }
            }
        }

        return array_values($names);
    }

    /** @return list<MapName> */
    public static function fromDirectory(string $directory): array
    {
        $names = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() !== 'as') {
                continue;
            }

            foreach (self::fromSource((string) file_get_contents($file->getPathname())) as $name) {
                $names[$name->value] = $name;
            }
        }

        return array_values($names);
    }
}
