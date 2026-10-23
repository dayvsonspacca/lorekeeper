<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat;

use Lorekeeper\Core\Game\Attribute\Stat\Boost\Boost;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\Boosts;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\Chance;
use Lorekeeper\Core\Game\Attribute\Stat\Chance\Chances;
use Lorekeeper\Core\Game\Attribute\Stat\Power\Power;
use Lorekeeper\Core\Game\Attribute\Stat\Power\Powers;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\PrimaryAttribute;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\PrimaryAttributes;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\Resistance;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\Resistances;

final class Stats
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public private(set) PrimaryAttributes $primary = new PrimaryAttributes(),
        public private(set) Powers $powers = new Powers(),
        public private(set) Chances $chances = new Chances(),
        public private(set) Haste $haste = new Haste(0.0),
        public private(set) CriticalMultiplier $criticalMultiplier = new CriticalMultiplier(1.5),
        public private(set) Resistances $resistances = new Resistances(),
        public private(set) Boosts $boosts = new Boosts(),
        public private(set) HealingIntake $healingIntake = new HealingIntake(1.0),
    ) {}

    /**
     * @mago-expect analysis:match-not-exhaustive
     * @mago-expect lint:halstead
     */
    public function update(Stat $stat): void
    {
        match (true) {
            $stat instanceof PrimaryAttribute => $this->primary->update($stat),
            $stat instanceof Power => $this->powers->update($stat),
            $stat instanceof Chance => $this->chances->update($stat),
            $stat instanceof Resistance => $this->resistances->update($stat),
            $stat instanceof Boost => $this->boosts->update($stat),
            $stat instanceof Haste => $this->haste = $stat,
            $stat instanceof CriticalMultiplier => $this->criticalMultiplier = $stat,
            $stat instanceof HealingIntake => $this->healingIntake = $stat,
        };
    }
}
