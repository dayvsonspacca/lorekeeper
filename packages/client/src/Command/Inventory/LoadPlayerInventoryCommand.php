<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Inventory;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class LoadPlayerInventoryCommand implements Command
{
    public function __construct(
        private AreaId $areaId,
        private PlayerSpawnId $spawnId,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%retrieveInventory%{$this->areaId}%{$this->spawnId}%";
    }
}
