<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\Launcher\NativeLauncher;
use Lorekeeper\Apps\Desktop\Bots\Launcher\ProcessLauncher;
use Lorekeeper\Apps\Desktop\Bots\Notifier\NativeNotifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\SilentNotifier;
use Lorekeeper\Apps\Desktop\Native\MainWindow;
use Lorekeeper\Apps\Desktop\Native\Tray;
use Native\Desktop\Events\MenuBar\MenuBarCreated;
use Native\Desktop\Facades\MenuBar;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(Launcher::class, static fn(): Launcher => config('nativephp-internal.running')
            ? new NativeLauncher()
            : new ProcessLauncher());
        $this->app->singleton(Notifier::class, static fn(): Notifier => config('nativephp-internal.running')
            ? new NativeNotifier()
            : new SilentNotifier());
    }

    public function boot(): void
    {
        Event::listen(MenuBarCreated::class, static fn() => MenuBar::contextMenu(Tray::menu()));
        Event::listen(Tray::OPEN_WINDOW, MainWindow::open(...));
    }
}
