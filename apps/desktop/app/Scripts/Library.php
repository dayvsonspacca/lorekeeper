<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Lorekeeper\Apps\Desktop\Models\Script;

final readonly class Library
{
    /** @return array<int|string, array<int, string>|string> */
    public static function playable(): array
    {
        $loose = [];
        $grouped = [];
        foreach (Script::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get() as $script) {
            if ($script->problems() !== []) {
                continue;
            }

            if ($script->group === null) {
                $loose[$script->id] = $script->name;

                continue;
            }

            $grouped[$script->group][$script->id] = $script->name;
        }

        return $loose + $grouped;
    }

    /** @return list<string> */
    public static function groups(): array
    {
        return Script::query()
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();
    }
}
