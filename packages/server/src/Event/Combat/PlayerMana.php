<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Player\PlayerName;

final readonly class PlayerMana
{
    public function __construct(
        public PlayerName $player,
        public Mana $mana,
    ) {}
}
