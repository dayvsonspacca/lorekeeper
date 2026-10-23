<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;

final readonly class Hero extends Player
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        PlayerSpawnId $spawnId,
        PlayerName $name,
        PlayerLevel $level,
        public HeroAttributes $attributes,
        public HeroClass $class,
        public HeroProgression $progression,
        public HeroInventory $inventory,
        public HeroSettings $settings = new HeroSettings(),
    ) {
        parent::__construct($spawnId, $name, $level, $attributes->vitals);
    }
}
