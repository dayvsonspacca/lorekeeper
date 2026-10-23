<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Boost;

final readonly class Boosts
{
    public function __construct(
        public DamageBoost $damage = new DamageBoost(0.0),
        public PhysicalBoost $physical = new PhysicalBoost(0.0),
        public MagicalBoost $magical = new MagicalBoost(0.0),
        public HealingBoost $healing = new HealingBoost(0.0),
        public DotBoost $dot = new DotBoost(0.0),
    ) {}
}
