<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

use Lorekeeper\Core\World\Quest\Objective\QuestObjective;
use Lorekeeper\Core\World\Quest\Requirement\QuestRequirement;
use Lorekeeper\Core\World\Quest\Reward\QuestReward;
use Psl\Option\Option;

final readonly class Quest
{
    /**
     * @param Option<QuestDescription> $description
     * @param list<QuestRequirement> $requirements
     * @param list<QuestObjective> $objectives
     * @param list<QuestReward> $rewards
     *
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public QuestId $id,
        public QuestName $name,
        public Option $description,
        public array $requirements,
        public array $objectives,
        public array $rewards,
    ) {}
}
