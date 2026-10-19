<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Support\Gauge;

final readonly class PlayerLevelProgress
{
    /** @param Gauge<Experience> $experience */
    public function __construct(
        public PlayerLevel $level,
        public Gauge $experience,
    ) {}
}
