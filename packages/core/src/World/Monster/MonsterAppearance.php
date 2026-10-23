<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Lorekeeper\Core\Game\Asset\Gamefile;
use Lorekeeper\Core\Game\Asset\Linkage;

final readonly class MonsterAppearance
{
    public function __construct(
        public Gamefile $file,
        public Linkage $linkage,
    ) {}
}
