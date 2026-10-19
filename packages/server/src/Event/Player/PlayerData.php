<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Player;

use Lorekeeper\Core\Player\PlayerLevelProgress;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerQuestMarks;
use Lorekeeper\Core\Player\Wallet;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\SocketId;

final readonly class PlayerData
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public SocketId $socketId,
        public PlayerName $name,
        public PlayerLevelProgress $progress,
        public Wallet $wallet,
        public bool $member,
        public Quantity $bagSlots,
        public PlayerQuestMarks $marks,
    ) {}
}
