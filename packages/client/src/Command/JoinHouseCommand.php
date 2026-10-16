<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

use Lorekeeper\Core\Player\PlayerName;
use Override;

final readonly class JoinHouseCommand implements Command
{
    public function __construct(
        private PlayerName $owner,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%house%1%{$this->owner}%";
    }
}
