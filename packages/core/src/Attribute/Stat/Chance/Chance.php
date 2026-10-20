<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Chance;

use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\Ratio;

abstract readonly class Chance extends Ratio implements Stat {}
