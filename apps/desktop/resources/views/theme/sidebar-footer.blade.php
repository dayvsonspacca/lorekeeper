@php
    use Lorekeeper\Apps\Desktop\Filament\Pages\ManagePreferences;
@endphp

<nav class="fi-sidebar-nav lk-sidebar-bottom">
    <ul class="fi-sidebar-nav-groups">
        <li class="fi-sidebar-group">
            <ul class="fi-sidebar-group-items">
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
