<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class ClassId extends PositiveInteger implements Subject {}
