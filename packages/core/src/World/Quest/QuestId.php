<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class QuestId extends PositiveInteger implements Subject {}
