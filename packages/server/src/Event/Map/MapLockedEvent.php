<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Server\Event\NoticeEvent;
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

    #[Override]
    public function summary(): string
    {
        return 'The map is locked';
    }
}
