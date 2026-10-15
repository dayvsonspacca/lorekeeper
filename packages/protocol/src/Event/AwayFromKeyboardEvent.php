<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event;

use Override;

final readonly class AwayFromKeyboardEvent extends NoticeEvent
{
    #[Override]
    protected static function channel(): string
    {
        return 'server';
    }

    #[Override]
    protected static function phrase(): string
    {
        return 'away from keyboard';
    }
}
