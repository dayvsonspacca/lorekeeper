<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Map\ExitPrompt;
use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapName;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class MapNameExtractor
{
    private const array MENTIONS = [
        '/gotoTown\("([^"]+)"/',
        '/strNewMap\s*=\s*"([^"]+)"/',
        '/strString\s*=\s*"([^"]+)"/',
        '/strMap\s*=\s*"([^"]+)"/',
    ];

    /** @return list<MapName> */
    public static function mentionsIn(string $code): array
    {
        return self::matching(self::MENTIONS, $code);
    }

    /** @return list<MapExit> */
    public static function exitsIn(string $code): array
    {
        $exits = [];
        $blocks = preg_split('/internal function __setProp_/', $code);
        foreach ($blocks === false ? [] : $blocks as $block) {
            $target = [];
            if (preg_match('/strNewMap\s*=\s*"([^"]+)"/', $block, $target) !== 1) {
                continue;
            }

            $map = MapNames::valid([$target[1]])[0] ?? null;
            if ($map === null) {
                continue;
            }

            $ask = [];
            $prompt = preg_match('/strAsk\s*=\s*"([^"]+)"/', $block, $ask) === 1
                ? new ExitPrompt(trim(str_replace(['\\\'', '\\"'], ["'", '"'], $ask[1])))
                : null;
            $exits[$map->value . '|' . ($prompt->value ?? '')] = new MapExit($map, $prompt);
        }

        return array_values($exits);
    }

    /** @return array{mentions: list<MapName>, exits: list<MapExit>} */
    public static function fromDirectory(string $directory): array
    {
        $mentions = [];
        $exits = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() !== 'as') {
                continue;
            }

            $code = (string) file_get_contents($file->getPathname());
            $mentions = [...$mentions, ...self::mentionsIn($code)];
            $exits = [...$exits, ...self::exitsIn($code)];
        }

        $unique = [];
        foreach ($exits as $exit) {
            $unique[$exit->mapName->value . '|' . ($exit->prompt->value ?? '')] = $exit;
        }

        return ['mentions' => self::unique($mentions), 'exits' => array_values($unique)];
    }

    /**
     * @param list<non-empty-string> $patterns
     *
     * @return list<MapName>
     */
    private static function matching(array $patterns, string $code): array
    {
        $candidates = [];
        foreach ($patterns as $pattern) {
            $matches = [];
            preg_match_all($pattern, $code, $matches);
            $candidates = [...$candidates, ...$matches[1]];
        }

        return MapNames::valid($candidates);
    }

    /**
     * @param list<MapName> $names
     *
     * @return list<MapName>
     */
    private static function unique(array $names): array
    {
        $unique = [];
        foreach ($names as $name) {
            $unique[$name->value] = $name;
        }

        return array_values($unique);
    }
}
