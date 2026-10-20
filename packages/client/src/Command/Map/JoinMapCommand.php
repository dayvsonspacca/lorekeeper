<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Map;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\RoomId;
use Override;
use Psl\Option;

final readonly class JoinMapCommand implements Command
{
    /** @param Option\Option<RoomId> $room */
    public function __construct(
        private PlayerName $playerName,
        private MapName $mapName,
        private Option\Option $room,
    ) {}

    public function inRoom(RoomId $room): self
    {
        return $this->room->isSome() ? $this : new self($this->playerName, $this->mapName, Option\some($room));
    }

    #[Override]
    public function toMessage(): string
    {
        return $this->room->proceed(
            fn(RoomId $room) => "%xt%zm%cmd%1%tfer%{$this->playerName}%{$this->mapName}-{$room}%",
            fn() => "%xt%zm%cmd%1%tfer%{$this->playerName}%{$this->mapName}%",
        );
    }
}
