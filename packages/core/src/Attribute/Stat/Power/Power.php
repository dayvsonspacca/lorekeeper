<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Power;

use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\NonNegativeInteger;

abstract readonly class Power extends NonNegativeInteger implements Stat {}
