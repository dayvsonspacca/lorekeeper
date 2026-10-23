<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Player\Hero\Progression\FactionsProgression;
use Lorekeeper\Core\Player\Hero\Progression\LevelProgression;
use Lorekeeper\Core\Player\Hero\Progression\QuestsProgression;

final readonly class HeroProgression
{
    public function __construct(
        public LevelProgression $level,
        public QuestsProgression $quests = new QuestsProgression(),
        public FactionsProgression $factions = new FactionsProgression(),
    ) {}
}
