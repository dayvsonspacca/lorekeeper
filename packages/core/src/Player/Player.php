<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Attribute\Stat\Stats;
use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Support\Tag\Taggable;
use Lorekeeper\Core\Support\Tag\Tags;

final readonly class Player
{
    /** @use Taggable<PlayerTag> */
    use Taggable;

    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public PlayerName $name,
        public PlayerLevelProgress $progress,
        public Vitals $vitals,
        public PlayerClass $class,
        public PlayerInventory $inventory,
        public Wallet $wallet,
        public PlayerQuestMarks $marks,
        public Stats $stats = new Stats(),
        public ?PlayerSettings $settings = null,
    ) {
        $this->tags = new Tags();
    }
}
