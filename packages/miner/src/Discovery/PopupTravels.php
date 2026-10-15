<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Map\MapName;

final readonly class PopupTravels
{
    /** @return list<MapName> */
    public static function in(string $code): array
    {
        $maps = [];
        $blocks = preg_split('/internal function __setProp_/', $code);
        foreach ($blocks === false ? [] : $blocks as $block) {
            foreach (self::targetsIn($block) as $target) {
                $maps[$target] = true;
            }
        }

        $calls = [];
        preg_match_all('/gotoTown\(\s*"([^"]+)"/', $code, $calls);
        foreach ($calls[1] as $target) {
            $maps[$target] = true;
        }

        return MapNames::valid(array_keys($maps));
    }

    /** @return list<string> */
    private static function targetsIn(string $block): array
    {
        if (str_contains($block, 'strCutLink')) {
            return [];
        }

        $targets = [];
        $match = [];
        if (
            str_contains($block, 'strAction = "/Join"')
            && preg_match('/strString\s*=\s*"([^"]+)"/', $block, $match) === 1
        ) {
            $targets[] = $match[1];
        }

        if (preg_match('/strMap\s*=\s*"([^"]+)"/', $block, $match) === 1) {
            $targets[] = $match[1];
        }

        return $targets;
    }
}
