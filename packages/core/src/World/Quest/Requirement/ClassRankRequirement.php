<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\Game\Rank\Rank;

final readonly class ClassRankRequirement implements QuestRequirement
{
    public function __construct(
        public ClassId $classId,
        public Rank $rank,
    ) {}
}
