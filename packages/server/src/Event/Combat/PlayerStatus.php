<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Player\PlayerName;

final readonly class PlayerStatus
{
    public function __construct(
        public PlayerName $name,
        public ?Health $health,
        public ?Mana $mana,
        public ?CombatState $state,
    ) {}
}
