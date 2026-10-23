<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Attribute\Stat\Stats;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Player\Player;
use Lorekeeper\Core\Player\PlayerName;

final readonly class Hero extends Player
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        PlayerName $name,
        public HeroLevelProgress $progress,
        Vitals $vitals,
        public HeroClass $class,
        public HeroInventory $inventory,
        public HeroWallet $wallet,
        public HeroQuestMarks $marks,
        public Stats $stats = new Stats(),
        public ?HeroSettings $settings = null,
    ) {
        parent::__construct($name, $progress->level, $vitals);
    }
}
