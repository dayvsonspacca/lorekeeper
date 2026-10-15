<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Command;

use Override;

final readonly class JoinInitialMapCommand implements Command
{
    #[Override]
    public function toMessage(): string
    {
        return '%xt%zm%firstJoin%1%';
    }
}
