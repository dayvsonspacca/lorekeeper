<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\Support\PositiveInteger;

final readonly class ShopId extends PositiveInteger implements Subject {}
