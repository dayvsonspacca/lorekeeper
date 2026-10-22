<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSettings;
use Lorekeeper\Server\SocketId;

final readonly class PlayerSettingsData
{
    public function __construct(
        public SocketId $socketId,
        public PlayerName $name,
        public PlayerSettings $settings,
    ) {}
}
