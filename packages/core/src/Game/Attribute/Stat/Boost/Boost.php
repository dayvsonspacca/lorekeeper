<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Boost;

use Lorekeeper\Core\Game\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\SignedRatio;

abstract readonly class Boost extends SignedRatio implements Stat {}
