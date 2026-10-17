<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Launcher;

use Lorekeeper\Apps\Desktop\Models\BotRun;
use Override;
use Symfony\Component\Process\Process;

/**
 * Plays each run in a detached PHP process, for when the app is served in a browser while developing.
 */
final readonly class ProcessLauncher implements Launcher
{
    #[Override]
    public function launch(BotRun $run): void
    {
        $command = implode(' ', array_map(escapeshellarg(...), [
            PHP_BINARY,
            base_path('artisan'),
            'bots:run',
            (string) $run->id,
        ]));

        Process::fromShellCommandline("nohup {$command} > /dev/null 2>&1 &", base_path())->run();
    }

    #[Override]
    public function alive(BotRun $run): bool
    {
        return $run->pid !== null && function_exists('posix_kill') && posix_kill($run->pid, signal: 0);
    }
}
