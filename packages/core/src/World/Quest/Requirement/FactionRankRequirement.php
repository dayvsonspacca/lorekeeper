<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Faction\FactionId;

final readonly class FactionRankRequirement implements QuestRequirement
{
    public function __construct(
        public FactionId $factionId,
        public Rank $rank,
    ) {}
}
