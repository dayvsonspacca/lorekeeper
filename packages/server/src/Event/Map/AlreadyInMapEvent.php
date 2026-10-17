<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Server\Event\NoticeEvent;
use Override;

final readonly class AlreadyInMapEvent extends NoticeEvent implements MapJoinedEvent
{
    #[Override]
    protected static function channel(): string
    {
        return 'warning';
    }

    #[Override]
    protected static function phrase(): string
    {
        return 'already in';
    }
}
