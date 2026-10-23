<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Game\Attribute\Stat\Stats;
use Lorekeeper\Core\Game\Attribute\Vitals;

final readonly class HeroAttributes
{
    public function __construct(
        public Vitals $vitals,
        public Stats $stats = new Stats(),
    ) {}
}
