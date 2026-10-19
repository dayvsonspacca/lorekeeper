<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Server\SocketId;

final readonly class RoomPlayer
{
    public function __construct(
        public SocketId $socketId,
        public MapPlayer $player,
    ) {}
}
