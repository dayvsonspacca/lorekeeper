<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Game\Combat\Traits\Targetable;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class PlayerSpawnId extends PositiveInteger implements Targetable {}
