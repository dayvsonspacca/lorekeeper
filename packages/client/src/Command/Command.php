<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

interface Command
{
    public function toMessage(): string;
}
