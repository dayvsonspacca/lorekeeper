<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Power;

use Lorekeeper\Core\Game\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\NonNegativeInteger;

abstract readonly class Power extends NonNegativeInteger implements Stat {}
