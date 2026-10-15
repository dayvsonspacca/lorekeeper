<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Event\Map;

use Lorekeeper\Protocol\Event\NoticeEvent;
use Override;

final readonly class MapLockedEvent extends NoticeEvent
{
    #[Override]
    protected static function channel(): string
    {
        return 'warning';
    }

    #[Override]
    protected static function phrase(): string
    {
        return 'map is locked';
    }
}
