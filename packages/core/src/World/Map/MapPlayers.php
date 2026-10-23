<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Core\Support\Collection;

/**
 * @extends Collection<PlayerSpawnId, MapPlayer>
 */
final class MapPlayers extends Collection
{
    /** @param MapPlayer $item */
    protected static function key(mixed $item): PlayerSpawnId
    {
        return $item->player->spawnId;
    }
}
