<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Progression;

use Lorekeeper\Core\Game\Rank\Rank;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\Reputation;

final readonly class FactionsProgression
{
    /** @param array<positive-int, Reputation> $reputations */
    public function __construct(
        private array $reputations = [],
    ) {}

    public function reputation(FactionId $faction): Reputation
    {
        return $this->reputations[$faction->value] ?? new Reputation(0);
    }

    public function rank(FactionId $faction): Rank
    {
        return Rank::of($this->reputation($faction));
    }
}
