<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Combat;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Player\PlayerSpawnId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class ResurrectCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private PlayerSpawnId $player,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%resPlayerTimed%{$this->area}%{$this->player}%";
    }
}
