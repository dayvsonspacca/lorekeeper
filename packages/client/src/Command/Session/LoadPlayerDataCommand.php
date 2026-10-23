<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Session;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class LoadPlayerDataCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private PlayerSpawnId $player,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%retrieveUserDatas%{$this->area}%{$this->player}%";
    }
}
