<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Command;

use Override;

final readonly class LogoutCommand implements Command
{
    #[Override]
    public function toMessage(): string
    {
        return '%xt%zm%logout%1%';
    }
}
