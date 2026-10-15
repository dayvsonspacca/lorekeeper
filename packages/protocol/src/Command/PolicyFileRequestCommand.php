<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Command;

use Override;

final readonly class PolicyFileRequestCommand implements Command
{
    #[Override]
    public function toMessage(): string
    {
        return '<policy-file-request/>';
    }
}
