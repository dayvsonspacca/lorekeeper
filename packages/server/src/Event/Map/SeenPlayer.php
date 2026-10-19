<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\World\Map\MapPlayer;
use Lorekeeper\Server\SocketId;

final readonly class SeenPlayer
{
    public function __construct(
        public SocketId $socket,
        public MapPlayer $player,
    ) {}
}
