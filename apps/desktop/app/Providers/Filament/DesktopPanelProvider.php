<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Lorekeeper\Apps\Desktop\Filament\Pages\Bots;
use Lorekeeper\Apps\Desktop\Filament\Pages\ManagePreferences;
use Lorekeeper\Apps\Desktop\Livewire\KeepAlive;

final class DesktopPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('desktop')
            ->path('')
            ->brandName('Lorekeeper')
            ->colors(['primary' => Color::hex('#818cf8'), 'gray' => self::gray()])
            ->font('IBM Plex Sans', url: asset('fonts/ibm-plex/ibm-plex.css'), provider: LocalFontProvider::class)
            ->defaultThemeMode(ThemeMode::Dark)
            ->topbar(false)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->spa()
            ->renderHook(PanelsRenderHook::HEAD_END, static fn(): View => view('theme.scene'))
            ->renderHook(PanelsRenderHook::HEAD_END, static fn(): View => view('theme.desktop'))
            ->renderHook(PanelsRenderHook::SIDEBAR_LOGO_BEFORE, static fn(): View => view('theme.sidebar-logo'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, static fn(): View => view('theme.sidebar-footer'))
            ->renderHook(PanelsRenderHook::BODY_END, static fn(): string => Blade::render('@livewire($component)', [
                'component' => KeepAlive::class,
            ]))
            ->pages([Bots::class, ManagePreferences::class])
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

    /** @return array<int, string> */
    private static function gray(): array
    {
        return array_map(Color::convertToOklch(...), [
            50 => '#f3f4f7',
            100 => '#e2e4ea',
            200 => '#d5d8e0',
            300 => '#b4b9c6',
            400 => '#8a90a0',
            500 => '#6b7180',
            600 => '#3a4050',
            700 => '#2a2f3a',
            800 => '#1f232b',
            900 => '#151820',
            950 => '#111318',
        ]);
    }
}
