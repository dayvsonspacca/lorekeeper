<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Command;

interface Command
{
    public function toMessage(): string;
}
