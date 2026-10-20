<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Pages;

use Filament\Resources\Pages\ViewRecord;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\AccountResource;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Actions\OpenRunAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Actions\StartRunAction;
use Override;

final class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    #[Override]
    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    #[Override]
    public function getSubheading(): string
    {
        return $this->getRecord()->server ?? 'First open server';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [StartRunAction::make(), OpenRunAction::make()];
    }
}
