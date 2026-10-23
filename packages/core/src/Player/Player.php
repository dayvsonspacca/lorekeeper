<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Tag\Tags;
use Lorekeeper\Core\Game\Tag\Traits\Taggable;

class Player
{
    /** @use Taggable<PlayerTag> */
    use Taggable;

    public function __construct(
        public readonly PlayerSpawnId $spawnId,
        public readonly PlayerName $name,
        public protected(set) PlayerLevel $level,
        public readonly Vitals $vitals,
    ) {
        $this->tags = new Tags();
    }
}
