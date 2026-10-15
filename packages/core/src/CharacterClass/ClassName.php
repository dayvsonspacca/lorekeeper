<?php

declare(strict_types=1);

namespace Lorekeeper\Core\CharacterClass;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\NonEmptyString;

final readonly class ClassName extends NonEmptyString implements Subject {}
