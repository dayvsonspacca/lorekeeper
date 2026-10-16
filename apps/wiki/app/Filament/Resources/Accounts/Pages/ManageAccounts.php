<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\AccountResource;
use Override;

final class ManageAccounts extends ManageRecords
{
    protected static string $resource = AccountResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
