<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Server\MonsterMapId;

final readonly class MonsterStatus
{
    public function __construct(
        public MonsterMapId $id,
        public ?Health $health,
        public ?CombatState $state,
    ) {}
}
