<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

use Override;

/**
 * The server refused the last command because it came too quickly after the previous one.
 *
 * The command is  dropped, not queued, so it has to be sent again after a short pause.
 *
 */
final readonly class ActionTooSoonEvent extends NoticeEvent
{
    #[Override]
    protected static function channel(): string
    {
        return 'warning';
    }

    #[Override]
    protected static function phrase(): string
    {
        return 'last action was too soon';
    }

    #[Override]
    public function summary(): string
    {
        return 'Action too soon';
    }
}
