<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\SocketId;
use Override;

final readonly class ResurrectCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private SocketId $player,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%resPlayerTimed%{$this->area}%{$this->player}%";
    }
}
