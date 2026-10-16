<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\AccountResource;
use Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Widgets\AccountStats;
use Override;

final class ManageAccounts extends ManageRecords
{
    protected static string $resource = AccountResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add account')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('Add account')
                ->modalIcon(Heroicon::OutlinedUserPlus)
                ->modalSubmitActionLabel('Add')
                ->createAnother(false),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [AccountStats::class];
    }
}
