<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Launcher;

use Lorekeeper\Apps\Desktop\Models\BotRun;

interface Launcher
{
    public function launch(BotRun $run): void;

    public function alive(BotRun $run): bool;
}
