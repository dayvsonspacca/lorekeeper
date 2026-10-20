<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Chance;

final readonly class Chances
{
    public function __construct(
        public HitChance $hit = new HitChance(0.9),
        public CriticalChance $critical = new CriticalChance(0.05),
        public Evasion $evasion = new Evasion(0.04),
    ) {}
}
