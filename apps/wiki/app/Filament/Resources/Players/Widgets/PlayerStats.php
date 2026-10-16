<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\Players\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lorekeeper\Apps\Wiki\Models\Player;
use Override;

final class PlayerStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    /** @return list<Stat> */
    #[Override]
    protected function getStats(): array
    {
        return [
            Stat::make('Players', Player::query()->count())->icon(Heroicon::OutlinedUsers),
            Stat::make('New today', Player::query()->where('created_at', '>=', now()->startOfDay())->count())
                ->icon(Heroicon::OutlinedSparkles)
                ->color('success'),
            Stat::make(
                'With AQW id',
                Player::query()->whereNotNull('aqw_id')->count(),
            )->icon(Heroicon::OutlinedIdentification),
        ];
    }
}
