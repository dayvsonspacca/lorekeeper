<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;

final readonly class PlayerData
{
    public function __construct(
        public PlayerSpawnId $spawnId,
        public PlayerName $name,
        public PlayerLevel $level,
    ) {}
}
