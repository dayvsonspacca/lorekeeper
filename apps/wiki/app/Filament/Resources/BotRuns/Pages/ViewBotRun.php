<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages;

use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Actions\RunAgainAction;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Actions\StopRunAction;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\BotRunResource;
use Override;

final class ViewBotRun extends ViewRecord
{
    protected static string $resource = BotRunResource::class;

    #[On('run-changed')]
    public function refreshRun(): void
    {
        $this->getRecord()->refresh();
    }

    #[Override]
    public function getTitle(): string
    {
        return "Run #{$this->getRecord()->getKey()}";
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [StopRunAction::make(), RunAgainAction::make()];
    }
}
