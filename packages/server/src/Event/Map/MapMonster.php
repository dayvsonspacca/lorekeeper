<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Monster\MonsterRace;

final readonly class MapMonster
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public MonsterId $id,
        public MonsterName $name,
        public MonsterLevel $level,
        public MonsterRace $race,
        public Gamefile $file,
        public Linkage $linkage,
    ) {}
}
