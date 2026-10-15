<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Requirement;

use Lorekeeper\Core\World\Quest\QuestId;

final readonly class PreviousQuestRequirement implements QuestRequirement
{
    public function __construct(
        public QuestId $questId,
    ) {}
}
