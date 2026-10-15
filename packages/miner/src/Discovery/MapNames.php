<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Map\MapName;

final readonly class MapNames
{
    private const string VALID = '/^[a-z][a-z0-9]+$/';

    /**
     * @param list<string> $candidates
     *
     * @return list<MapName>
     */
    public static function valid(array $candidates): array
    {
        $names = [];
        foreach ($candidates as $candidate) {
            $name = strtolower($candidate);
            if (preg_match(self::VALID, $name) !== 1) {
                continue;
            }

            $names[$name] = new MapName($name);
        }

        return array_values($names);
    }
}
