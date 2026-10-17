<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Session;

use Lorekeeper\Client\Command\Command;
use Override;

final readonly class LogoutCommand implements Command
{
    #[Override]
    public function toMessage(): string
    {
        return '%xt%zm%logout%1%';
    }
}
