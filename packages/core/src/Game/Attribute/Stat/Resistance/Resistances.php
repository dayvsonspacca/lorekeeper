<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Resistance;

final class Resistances
{
    public function __construct(
        public private(set) DamageResistance $damage = new DamageResistance(0.0),
        public private(set) PhysicalResistance $physical = new PhysicalResistance(0.0),
        public private(set) MagicalResistance $magical = new MagicalResistance(0.0),
        public private(set) DotResistance $dot = new DotResistance(0.0),
    ) {}

    /** @mago-expect analysis:match-not-exhaustive */
    public function update(Resistance $stat): void
    {
        match (true) {
            $stat instanceof DamageResistance => $this->damage = $stat,
            $stat instanceof PhysicalResistance => $this->physical = $stat,
            $stat instanceof MagicalResistance => $this->magical = $stat,
            $stat instanceof DotResistance => $this->dot = $stat,
        };
    }
}
