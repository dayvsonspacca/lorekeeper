<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Lorekeeper\Core\Game\Combat\Traits\Targetable;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class MonsterSpawnId extends PositiveInteger implements Targetable {}
