<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\SocketId;
use Override;

final readonly class LoadPlayerInventoryCommand implements Command
{
    public function __construct(
        private AreaId $areaId,
        private SocketId $socketId,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%retrieveInventory%{$this->areaId}%{$this->socketId}%";
    }
}
