<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Bots\Scene\Realm;
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

    public function stopAction(): Action
    {
        return Action::make('stop')
            ->icon(Heroicon::Stop)
            ->color('danger')
            ->size(Size::Small)
            ->requiresConfirmation()
            ->modalHeading(static fn(array $arguments): string => 'Stop ' . (BotRun::query()
                ->find($arguments['run'] ?? 0)
                ?->account
                ->name ?? 'this bot') . '?')
            ->modalDescription('The bot logs out and the run ends as stopped.')
            ->modalSubmitActionLabel('Stop')
            ->action(static function (array $arguments, Launcher $launcher): void {
                $found = BotRun::query()->find($arguments['run'] ?? 0);
                if ($found === null || !$found->status->isActive()) {
                    return;
                }

                $found->stop($launcher);
            });
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function getViewData(): array
    {
        $accounts = Account::query()
            ->with('latestRun')
            ->orderBy('name')
            ->get();
        $now = now()->getTimestamp();

        return [
            'accounts' => $accounts,
            'now' => $now,
            'realm' => Realm::of($accounts->all(), $now),
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
