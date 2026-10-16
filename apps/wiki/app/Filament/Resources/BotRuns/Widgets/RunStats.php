<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Bots\TaskStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotTask;
use Override;

final class RunStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '5s';

    /** @return list<Stat> */
    #[Override]
    protected function getStats(): array
    {
        return [
            Stat::make(
                'Running',
                BotRun::query()->whereIn('status', [RunStatus::Running, RunStatus::Stopping])->count(),
            )
                ->icon(Heroicon::OutlinedPlayCircle)
                ->color('success'),
            Stat::make(
                'Tasks waiting',
                BotTask::query()->where('status', TaskStatus::Pending)->count(),
            )->icon(Heroicon::OutlinedQueueList),
            Stat::make(
                'Tasks done today',
                BotTask::query()
                    ->where('status', TaskStatus::Done)
                    ->where('finished_at', '>=', now()->startOfDay())
                    ->count(),
            )->icon(Heroicon::OutlinedCheckCircle),
            Stat::make('Failed in 24h', BotTask::query()
                ->where('status', TaskStatus::Failed)
                ->where('finished_at', '>=', now()->subDay())
                ->count() + BotRun::query()
                ->whereIn('status', [RunStatus::Failed, RunStatus::Disconnected])
                ->where('finished_at', '>=', now()->subDay())
                ->count())
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }
}
