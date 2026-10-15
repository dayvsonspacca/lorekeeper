<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Command;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Protocol\RoomId;
use Override;
use Psl\Option\Option;

final readonly class JoinMapCommand implements Command
{
    /** @param Option<RoomId> $room */
    public function __construct(
        private PlayerName $playerName,
        private MapName $mapName,
        private Option $room,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return $this->room->proceed(
            fn(RoomId $room) => "%xt%zm%cmd%1%tfer%{$this->playerName}%{$this->mapName}-{$room}%",
            fn() => "%xt%zm%cmd%1%tfer%{$this->playerName}%{$this->mapName}%",
        );
    }
}
