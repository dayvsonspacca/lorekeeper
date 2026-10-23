<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Server\Event\NoticeEvent;
use Override;

/**
 * The server refused to move our player because the map needs a quest to be done first.
 */
final readonly class MapRequiresQuestEvent extends NoticeEvent
{
    #[Override]
    protected static function channel(): string
    {
        return 'warning';
    }

    #[Override]
    protected static function phrase(): string
    {
        return 'that map requires special quest actions';
    }

    #[Override]
    public function summary(): string
    {
        return 'That map requires special quest actions';
    }
}
