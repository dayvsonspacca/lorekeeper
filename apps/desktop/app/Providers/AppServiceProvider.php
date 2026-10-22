<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers;

use BladeUI\Icons\IconsManifest;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\Launcher\ProcessLauncher;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Lorekeeper\Apps\Desktop\Bots\Notifier\SilentNotifier;
use Lorekeeper\Apps\Desktop\Native\BootstrapCache;
use Lorekeeper\Apps\Desktop\Native\InstallationKey;
use Lorekeeper\Apps\Desktop\Native\LocalTime;
use Lorekeeper\Apps\Desktop\Tauri\Shell;
use Lorekeeper\Apps\Desktop\Tauri\TauriNotifier;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        config(['desktop.timezone' => LocalTime::detect()]);

        if (Shell::running()) {
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

        $this->app->singleton(Launcher::class, ProcessLauncher::class);
        $this->app->singleton(Notifier::class, static fn(): Notifier => Shell::running()
            ? new TauriNotifier()
            : new SilentNotifier());
    }
}
