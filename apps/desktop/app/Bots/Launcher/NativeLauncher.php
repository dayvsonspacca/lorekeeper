<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Launcher;

use Lorekeeper\Apps\Desktop\Models\BotRun;
use Native\Desktop\Facades\ChildProcess;
use Override;

final readonly class NativeLauncher implements Launcher
{
    #[Override]
    public function launch(BotRun $run): void
    {
        ChildProcess::artisan(['bots:run', (string) $run->id], self::alias($run));
    }

    #[Override]
    public function alive(BotRun $run): bool
    {
        return ChildProcess::get(self::alias($run)) !== null;
    }

    private static function alias(BotRun $run): string
    {
        return "bot-run-{$run->id}";
    }
}
