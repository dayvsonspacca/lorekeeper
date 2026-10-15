<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Region;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class RegionId extends PositiveInteger implements Subject {}
