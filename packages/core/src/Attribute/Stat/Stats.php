<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat;

use Lorekeeper\Core\Attribute\Stat\Boost\Boosts;
use Lorekeeper\Core\Attribute\Stat\Chance\Chances;
use Lorekeeper\Core\Attribute\Stat\Power\Powers;
use Lorekeeper\Core\Attribute\Stat\Primary\PrimaryAttributes;
use Lorekeeper\Core\Attribute\Stat\Resistance\Resistances;

final readonly class Stats
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public PrimaryAttributes $primary = new PrimaryAttributes(),
        public Powers $powers = new Powers(),
        public Chances $chances = new Chances(),
        public Haste $haste = new Haste(0.0),
        public CriticalMultiplier $criticalMultiplier = new CriticalMultiplier(1.5),
        public Resistances $resistances = new Resistances(),
        public Boosts $boosts = new Boosts(),
        public HealingIntake $healingIntake = new HealingIntake(1.0),
    ) {}
}
