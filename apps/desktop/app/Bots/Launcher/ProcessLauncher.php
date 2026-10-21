<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Launcher;

use Lorekeeper\Apps\Desktop\Models\Account;
use Override;
use Symfony\Component\Process\Process;

final readonly class ProcessLauncher implements Launcher
{
    #[Override]
    public function launch(Account $account): void
    {
        $command = implode(' ', array_map(escapeshellarg(...), [
            PHP_BINARY,
            base_path('artisan'),
            'bots:play',
            (string) $account->id,
        ]));

        Process::fromShellCommandline("nohup {$command} > /dev/null 2>&1 &", base_path())->run();
    }

    #[Override]
    public function alive(Account $account): bool
    {
        return $account->pid !== null && function_exists('posix_kill') && posix_kill($account->pid, signal: 0);
    }
}
