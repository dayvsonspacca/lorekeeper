<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Server\Event\EventBundle;
use Override;

final readonly class MapJoins extends EventBundle
{
    #[Override]
    public static function events(): array
    {
        return [MovedToMapEvent::class, AlreadyInMapEvent::class];
    }
}
