<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers;

use Lorekeeper\Apps\Desktop\Models\BotRun;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;
use Override;

final class NativeAppServiceProvider implements ProvidesPhpIni
{
    /** Runs when the desktop app starts. */
    public function boot(): void
    {
        BotRun::interrupted();

        Window::open()
            ->title('Lorekeeper')
            ->width(1280)
            ->height(820)
            ->minWidth(900)
            ->minHeight(600)
            ->rememberState();
    }

    /** @return array<string, string> */
    #[Override]
    public function phpIni(): array
    {
        return ['memory_limit' => '512M'];
    }
}
