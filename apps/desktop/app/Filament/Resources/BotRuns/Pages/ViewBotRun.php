<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages;

use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions\AddTaskAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions\RunAgainAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions\StopRunAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource;
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
        return [AddTaskAction::make(), StopRunAction::make(), RunAgainAction::make()];
    }
}
