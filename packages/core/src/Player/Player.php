<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Attribute\Vitals;

final readonly class Player
{
    public function __construct(
        public PlayerName $name,
        public PlayerLevel $level,
        public Vitals $vitals,
        public PlayerClass $class,
    ) {}
}
