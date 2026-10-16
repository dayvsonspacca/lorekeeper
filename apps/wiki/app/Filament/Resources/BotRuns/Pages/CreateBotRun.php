<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\BotRunResource;
use Override;

final class CreateBotRun extends CreateRecord
{
    protected static string $resource = BotRunResource::class;

    protected static ?string $title = 'Start run';

    protected static bool $canCreateAnother = false;

    #[Override]
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');
        $this->form->fill(['parameters' => BotScript::blankParameters()]);
        $this->callHook('afterFill');
    }

    #[Override]
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Start')->icon(Heroicon::OutlinedPlay);
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return BotRunResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
