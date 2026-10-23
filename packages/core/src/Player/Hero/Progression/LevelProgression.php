<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Progression;

use Lorekeeper\Core\Game\Level\Experience;
use Lorekeeper\Core\Support\Gauge;

final class LevelProgression
{
    /** @param Gauge<Experience> $experience */
    public function __construct(
        public private(set) Gauge $experience,
    ) {}

    public function earn(Experience $amount): void
    {
        $maximum = $this->experience->maximum;
        $earned = min($maximum->value, $this->experience->current->value + $amount->value);
        $this->experience = new Gauge(new Experience($earned), $maximum);
    }

    public function next(Experience $toNext): void
    {
        $this->experience = new Gauge(new Experience(0), $toNext);
    }

    /** @param Gauge<Experience> $experience */
    public function recount(Gauge $experience): void
    {
        $this->experience = $experience;
    }
}
