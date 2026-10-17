<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Lorekeeper\Core\Combat\Targetable;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class MonsterSpawnId extends PositiveInteger implements Targetable {}
