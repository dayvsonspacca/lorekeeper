<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Mention;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Faction\FactionId;

interface Mentions
{
    /** @return list<Mention<ItemId|FactionId, object>> */
    public function mentions(): array;
}
