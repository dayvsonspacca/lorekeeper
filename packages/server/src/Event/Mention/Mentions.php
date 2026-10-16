<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Mention;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Monster\MonsterId;

interface Mentions
{
    /** @return list<Mention<ItemId|FactionId|MonsterId, object>> */
    public function mentions(): array;
}
