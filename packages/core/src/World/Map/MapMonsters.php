<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Support\Collection;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;

/**
 * @extends Collection<MonsterSpawnId, MapMonster>
 */
final class MapMonsters extends Collection
{
    /** @param MapMonster $item */
    protected static function key(mixed $item): MonsterSpawnId
    {
        return $item->id;
    }
}
