<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Resistance;

final readonly class Resistances
{
    public function __construct(
        public DamageResistance $damage = new DamageResistance(0.0),
        public PhysicalResistance $physical = new PhysicalResistance(0.0),
        public MagicalResistance $magical = new MagicalResistance(0.0),
        public DotResistance $dot = new DotResistance(0.0),
    ) {}
}
