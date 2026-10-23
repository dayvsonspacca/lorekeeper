<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Chance;

final class Chances
{
    public function __construct(
        public private(set) HitChance $hit = new HitChance(0.9),
        public private(set) CriticalChance $critical = new CriticalChance(0.05),
        public private(set) Evasion $evasion = new Evasion(0.04),
    ) {}

    /** @mago-expect analysis:match-not-exhaustive */
    public function update(Chance $stat): void
    {
        match (true) {
            $stat instanceof HitChance => $this->hit = $stat,
            $stat instanceof CriticalChance => $this->critical = $stat,
            $stat instanceof Evasion => $this->evasion = $stat,
        };
    }
}
