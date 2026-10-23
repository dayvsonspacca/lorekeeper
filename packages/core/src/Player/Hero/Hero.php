<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Game\Attribute\Stat\Stats;
use Lorekeeper\Core\Game\Attribute\Vitals;
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
        public HeroProgression $progression,
        Vitals $vitals,
        public HeroClass $class,
        public HeroInventory $inventory,
        public HeroWallet $wallet,
        public Stats $stats = new Stats(),
        public ?HeroSettings $settings = null,
    ) {
        parent::__construct($spawnId, $name, $level, $vitals);
    }
}
