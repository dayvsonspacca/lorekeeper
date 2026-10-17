<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Override;

/** @implements Script<null> */
final readonly class LogoutScript implements Script
{
    /** @return Generator<int, Send, null|Event|TimedOut, null> */
    #[Override]
    public function run(): Generator
    {
        yield new Send(new LogoutCommand());

        return null;
    }
}
