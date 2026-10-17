<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Launcher;

use Lorekeeper\Apps\Desktop\Models\BotRun;

/**
 * Starts the process that plays a run and tells whether it is still there.
 */
interface Launcher
{
    public function launch(BotRun $run): void;

    public function alive(BotRun $run): bool;
}
