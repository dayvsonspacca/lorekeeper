<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player\Hero;

use Lorekeeper\Core\Player\Hero\HeroSettings;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;

final readonly class HeroSettingsData
{
    public function __construct(
        public PlayerSpawnId $spawnId,
        public PlayerName $name,
        public HeroSettings $settings,
    ) {}
}
