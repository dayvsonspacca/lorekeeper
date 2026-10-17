<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;
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
                'Runs today',
                BotRun::query()->where('created_at', '>=', now()->startOfDay())->count(),
            )->icon(Heroicon::OutlinedPlay),
            Stat::make(
                'Failed in 24h',
                BotRun::query()
                    ->whereIn('status', [RunStatus::Failed, RunStatus::Disconnected])
                    ->where('finished_at', '>=', now()->subDay())
                    ->count(),
            )
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }
}
