<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\LogoutCommand;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Override;

/** @implements Script<null> */
final readonly class LogoutScript implements Script
{
    #[Override]
    public function run(): Generator
    {
        yield new Send(new LogoutCommand());

        return null;
    }
}
