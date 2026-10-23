<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Boost;

final class Boosts
{
    public function __construct(
        public private(set) DamageBoost $damage = new DamageBoost(0.0),
        public private(set) PhysicalBoost $physical = new PhysicalBoost(0.0),
        public private(set) MagicalBoost $magical = new MagicalBoost(0.0),
        public private(set) HealingBoost $healing = new HealingBoost(0.0),
        public private(set) DotBoost $dot = new DotBoost(0.0),
    ) {}

    /** @mago-expect analysis:match-not-exhaustive */
    public function update(Boost $stat): void
    {
        match (true) {
            $stat instanceof DamageBoost => $this->damage = $stat,
            $stat instanceof PhysicalBoost => $this->physical = $stat,
            $stat instanceof MagicalBoost => $this->magical = $stat,
            $stat instanceof HealingBoost => $this->healing = $stat,
            $stat instanceof DotBoost => $this->dot = $stat,
        };
    }
}
