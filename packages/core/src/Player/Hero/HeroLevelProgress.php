<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Support\Gauge;

final readonly class HeroLevelProgress
{
    /** @param Gauge<Experience> $experience */
    public function __construct(
        public PlayerLevel $level,
        public Gauge $experience,
    ) {}
}
