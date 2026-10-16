<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\SocketId;

final readonly class InGame
{
    public function __construct(
        public SocketId $socketId,
        public MovedToMapEvent $map,
    ) {}
}
