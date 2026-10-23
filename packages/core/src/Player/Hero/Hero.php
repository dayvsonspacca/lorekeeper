<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\Hero\Class\HeroClass;
use Lorekeeper\Core\Player\Hero\Inventory\HeroInventory;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSpawnId;

final class Hero extends Player
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        PlayerSpawnId $spawnId,
        PlayerName $name,
        PlayerLevel $level,
        public readonly HeroAttributes $attributes,
        public readonly HeroClass $class,
        public readonly HeroProgression $progression,
        public readonly HeroInventory $inventory,
        public readonly HeroSettings $settings = new HeroSettings(),
    ) {
        parent::__construct($spawnId, $name, $level, $attributes->vitals);
    }

    public function up(Experience $toNext): void
    {
        $this->level = $this->level->up();
        $this->progression->level->next($toNext);
    }
}
