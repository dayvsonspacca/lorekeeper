<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Lorekeeper\Apps\Desktop\Filament\Pages\Bots;

final class DesktopPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('desktop')
            ->path('')
            ->brandName('Lorekeeper')
            ->colors(['primary' => Color::Amber, 'gray' => Color::Zinc])
            ->defaultThemeMode(ThemeMode::Dark)
            ->topNavigation()
            ->spa()
            ->pages([Bots::class])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'Lorekeeper\Apps\Desktop\Filament\Resources')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
