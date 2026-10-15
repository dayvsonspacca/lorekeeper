<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\NonEmptyString;

final readonly class MapName extends NonEmptyString implements Subject {}
