<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

use Override;

/**
 * The server refused the last command because our player left combat too recently.
 */
final readonly class TooSoonAfterCombatEvent extends NoticeEvent
{
    #[Override]
    protected static function channel(): string
    {
        return 'warning';
    }

    #[Override]
    protected static function phrase(): string
    {
        return 'too soon after combat';
    }

    #[Override]
    public function summary(): string
    {
        return 'Too soon after combat';
    }
}
