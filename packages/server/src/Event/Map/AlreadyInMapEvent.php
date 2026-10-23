<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Server\Event\NoticeEvent;
use Override;

/**
 * The server refused to move our player because they are already in that map.
 */
final readonly class AlreadyInMapEvent extends NoticeEvent
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

    #[Override]
    public function summary(): string
    {
        return 'Already in that map';
    }
}
