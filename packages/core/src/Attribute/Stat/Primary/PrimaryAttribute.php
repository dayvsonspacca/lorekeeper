<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Primary;

use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\NonNegativeInteger;

abstract readonly class PrimaryAttribute extends NonNegativeInteger implements Stat {}
