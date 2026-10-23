<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Tag\Tags;
use Lorekeeper\Core\Game\Tag\Traits\Taggable;

readonly class Player
{
    /** @use Taggable<PlayerTag> */
    use Taggable;

    public function __construct(
        public PlayerSpawnId $spawnId,
        public PlayerName $name,
        public PlayerLevel $level,
        public Vitals $vitals,
    ) {
        $this->tags = new Tags();
    }
}
