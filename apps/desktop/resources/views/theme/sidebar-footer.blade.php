@php
    use Lorekeeper\Apps\Desktop\Filament\Pages\ManagePreferences;
    use Lorekeeper\Apps\Desktop\Native\Releases\Releases;

    $release = Releases::make()->available();
@endphp

<nav class="fi-sidebar-nav lk-sidebar-bottom">
    <ul class="fi-sidebar-nav-groups">
        <li class="fi-sidebar-group">
            <ul class="fi-sidebar-group-items">
                @if ($release !== null)
                    <x-filament-panels::sidebar.item
                        icon="heroicon-o-arrow-down-circle"
                        :url="$release->url"
                        :should-open-url-in-new-tab="true"
                        badge="New"
                        badge-color="primary"
                    >
                        Download Lorekeeper {{ $release->version }}
                    </x-filament-panels::sidebar.item>
                @endif
                <x-filament-panels::sidebar.item
                    :active="request()->routeIs(ManagePreferences::getRouteName())"
                    :icon="ManagePreferences::getNavigationIcon()"
                    :url="ManagePreferences::getUrl()"
                >
                    {{ ManagePreferences::getNavigationLabel() }}
                </x-filament-panels::sidebar.item>
            </ul>
        </li>
    </ul>
</nav>
