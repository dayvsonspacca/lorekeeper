<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\Player\PlayerLevel;

final readonly class PlayerLevelRequirement implements QuestRequirement
{
    public function __construct(
        public PlayerLevel $level,
    ) {}
}
