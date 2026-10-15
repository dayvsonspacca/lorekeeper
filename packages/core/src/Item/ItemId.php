<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class ItemId extends PositiveInteger implements Subject {}
