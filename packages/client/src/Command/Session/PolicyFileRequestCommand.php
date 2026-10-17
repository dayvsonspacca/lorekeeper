<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Session;

use Lorekeeper\Client\Command\Command;
use Override;

final readonly class PolicyFileRequestCommand implements Command
{
    #[Override]
    public function toMessage(): string
    {
        return '<policy-file-request/>';
    }
}
