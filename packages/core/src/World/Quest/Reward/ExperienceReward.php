<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

use Lorekeeper\Core\Progression\Experience;

final readonly class ExperienceReward implements QuestReward
{
    public function __construct(
        public Experience $experience,
    ) {}
}
