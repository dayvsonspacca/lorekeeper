<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat\Helper;

use Lorekeeper\Core\Game\Combat\CombatState;

final readonly class CombatStates
{
    /** @param int<0, 2> $state */
    public static function read(int $state): CombatState
    {
        return match ($state) {
            0 => CombatState::Dead,
            2 => CombatState::Fighting,
            default => CombatState::Idle,
        };
    }
}
