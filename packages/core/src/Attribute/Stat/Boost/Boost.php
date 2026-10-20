<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Boost;

use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\SignedRatio;

abstract readonly class Boost extends SignedRatio implements Stat {}
