<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Server\MonsterMapId;

final readonly class MonsterPlacement
{
    public function __construct(
        public MonsterMapId $id,
        public MonsterId $monster,
        public Cell $cell,
        public ?MonsterLevel $level,
    ) {}
}
