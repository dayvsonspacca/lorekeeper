<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers;

use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Native\MainWindow;
use Lorekeeper\Apps\Desktop\Native\Tray;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\ChildProcess;
use Override;

final class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        BotRun::interrupted();

        MainWindow::open();
        Tray::create();
        ChildProcess::artisan(['tray:watch'], 'tray', persistent: true);
    }

    /** @return array<string, string> */
    #[Override]
    public function phpIni(): array
    {
        return ['memory_limit' => '512M'];
    }
}
