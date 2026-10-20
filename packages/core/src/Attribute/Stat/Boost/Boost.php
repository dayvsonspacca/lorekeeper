<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Boost;

use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\Ratio;

abstract readonly class Boost extends Ratio implements Stat {}
