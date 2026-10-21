<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers;

use BladeUI\Icons\IconsManifest;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\Launcher\NativeLauncher;
use Lorekeeper\Apps\Desktop\Bots\Launcher\ProcessLauncher;
use Lorekeeper\Apps\Desktop\Bots\Notifier\NativeNotifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\SilentNotifier;
use Lorekeeper\Apps\Desktop\Native\BootstrapCache;
use Lorekeeper\Apps\Desktop\Native\InstallationKey;
use Lorekeeper\Apps\Desktop\Native\LocalTime;
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
        config(['desktop.timezone' => LocalTime::detect()]);

        if (config('nativephp-internal.running') && !config('app.debug')) {
            config(['app.key' => new InstallationKey(storage_path('installation.key'))->read(config('app.cipher'))]);
        }

        $cache = BootstrapCache::folder(getenv());
        if ($cache !== null) {
            config(['filament.cache_path' => "{$cache}/filament"]);
            $this->app->singleton(
                IconsManifest::class,
                static fn(Application $app): IconsManifest => new IconsManifest(
                    new Filesystem(),
                    "{$cache}/blade-icons.php",
                    $app->make(Factory::class),
                ),
            );
        }

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
