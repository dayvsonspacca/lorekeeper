<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;

final readonly class MapPlayer
{
    public function __construct(
        public PlayerName $name,
        public ?PlayerLevel $level,
    ) {}
}
