<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Player\PlayerName;

final readonly class PlayerHealth
{
    public function __construct(
        public PlayerName $player,
        public Health $health,
    ) {}
}
