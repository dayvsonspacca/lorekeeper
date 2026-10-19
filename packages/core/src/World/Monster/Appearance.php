<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\Asset\Linkage;

final readonly class Appearance
{
    public function __construct(
        public Gamefile $file,
        public Linkage $linkage,
    ) {}
}
