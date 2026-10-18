<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\AccountResource;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Override;

final class Bots extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Bots';

    protected string $view = 'filament.pages.bots';

    #[Override]
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function start(int $account): void
    {
        Account::query()->find($account)?->start();
    }

    public function resume(int $account): void
    {
        Account::query()->find($account)?->resume();
    }

    public function stop(int $run, Launcher $launcher): void
    {
        $found = BotRun::query()->find($run);
        if ($found === null || !$found->status->isActive()) {
            return;
        }

        $found->stop($launcher);
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function getViewData(): array
    {
        return [
            'accounts' => Account::query()
                ->with('latestRun')
                ->orderBy('name')
                ->get(),
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('addAccount')
                ->label('Add account')
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalHeading('Add account')
                ->modalSubmitActionLabel('Add')
                ->schema(AccountResource::form(...))
                ->action(static function (array $data): void {
                    Account::query()->create($data);
                }),
        ];
    }
}
