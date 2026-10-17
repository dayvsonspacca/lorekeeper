<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers;

use Illuminate\Support\ServiceProvider;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\Launcher\NativeLauncher;
use Lorekeeper\Apps\Desktop\Bots\Launcher\ProcessLauncher;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(Launcher::class, static fn(): Launcher => config('nativephp-internal.running')
            ? new NativeLauncher()
            : new ProcessLauncher());
    }
}
