<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Faction;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class FactionId extends PositiveInteger implements Subject {}
