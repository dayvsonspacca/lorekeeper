<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event;

use Override;

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
}
