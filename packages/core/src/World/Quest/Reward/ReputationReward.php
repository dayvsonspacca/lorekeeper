<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

use Lorekeeper\Core\Progression\Reputation;
use Lorekeeper\Core\World\Faction\FactionId;

final readonly class ReputationReward implements QuestReward
{
    public function __construct(
        public FactionId $factionId,
        public Reputation $reputation,
    ) {}
}
