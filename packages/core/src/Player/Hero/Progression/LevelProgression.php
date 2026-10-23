<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Progression;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Support\Gauge;

final readonly class LevelProgression
{
    /** @param Gauge<Experience> $experience */
    public function __construct(
        public Gauge $experience,
    ) {}
}
