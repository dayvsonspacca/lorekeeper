<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Chance;

use Lorekeeper\Core\Game\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\Ratio;

abstract readonly class Chance extends Ratio implements Stat {}
