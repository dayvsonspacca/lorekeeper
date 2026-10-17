<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Override;

final class AccountStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '5s';

    /** @return list<Stat> */
    #[Override]
    protected function getStats(): array
    {
        $online = BotRun::query()
            ->whereIn('status', [RunStatus::Running, RunStatus::Stopping])
            ->distinct()
            ->count('account_id');

        return [
            Stat::make('Accounts', Account::query()->count())->icon(Heroicon::OutlinedUserGroup),
            Stat::make('Online now', $online)->icon(Heroicon::OutlinedSignal)->color('success'),
            Stat::make(
                'Runs today',
                BotRun::query()->where('created_at', '>=', now()->startOfDay())->count(),
            )->icon(Heroicon::OutlinedPlay),
        ];
    }
}
