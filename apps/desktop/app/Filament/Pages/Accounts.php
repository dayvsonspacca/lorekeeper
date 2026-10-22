<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Filament\Schemas\AccountForm;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\Script;
use Override;

final class Accounts extends Page
{
    private const int LOG_LINES = 50;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Accounts';

    protected string $view = 'filament.pages.accounts';

    public string $show = 'all';

    #[Override]
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function start(int $account): void
    {
        Account::query()->find($account)?->start();
    }

    public function stopAction(): Action
    {
        return Action::make('stop')
            ->color('danger')
            ->outlined()
            ->size(Size::Small)
            ->action(static function (array $arguments, Launcher $launcher): void {
                Account::query()
                    ->find($arguments['account'] ?? 0)
                    ?->stop($launcher);
            });
    }

    public function playScriptAction(): Action
    {
        return Action::make('playScript')
            ->label('Play')
            ->icon(Heroicon::OutlinedPlay)
            ->color('gray')
            ->outlined()
            ->size(Size::Small)
            ->record(static fn(array $arguments): ?Account => Account::query()->find($arguments['account'] ?? 0))
            ->modalHeading(static fn(?Account $record): string => 'Play a script with ' . ($record->name ?? 'this bot'))
            ->modalDescription('A bot that is off enters the game first.')
            ->modalSubmitActionLabel('Play')
            ->schema([
                Select::make('script')
                    ->label('Script')
                    ->options(
                        static fn(): array => Script::query()
                            ->orderBy('name')
                            ->get()
                            ->filter(static fn(Script $script): bool => $script->problems() === [])
                            ->mapWithKeys(static fn(Script $script): array => [$script->id => $script->name])
                            ->all(),
                    )
                    ->required(),
            ])
            ->action(static function (?Account $record, array $data): void {
                $script = Script::query()->find($data['script'] ?? 0);
                if ($record !== null && $script !== null) {
                    $record->play($script);
                }
            });
    }

    public function stopScriptAction(): Action
    {
        return Action::make('stopScript')
            ->label('Stop script')
            ->color('gray')
            ->outlined()
            ->size(Size::Small)
            ->action(static function (array $arguments): void {
                Account::query()
                    ->find($arguments['account'] ?? 0)
                    ?->stopScript();
            });
    }

    public function editAccountAction(): Action
    {
        return Action::make('editAccount')
            ->label('Edit')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->record(static fn(array $arguments): ?Account => Account::query()->find($arguments['account'] ?? 0))
            ->modalHeading(static fn(?Account $record): string => 'Edit ' . ($record->name ?? 'account'))
            ->modalSubmitActionLabel('Save')
            ->schema(AccountForm::editing(...))
            ->fillForm(static fn(?Account $record): array => [
                'name' => $record?->name,
                'server' => $record?->server,
            ])
            ->action(static function (?Account $record, array $data): void {
                $record?->update($data);
            });
    }

    public function deleteAccountAction(): Action
    {
        return Action::make('deleteAccount')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->record(static fn(array $arguments): ?Account => Account::query()->find($arguments['account'] ?? 0))
            ->modalHeading(static fn(?Account $record): string => 'Delete ' . ($record->name ?? 'this account') . '?')
            ->modalDescription('Its log goes with it.')
            ->modalSubmitActionLabel('Delete')
            ->hidden(static fn(?Account $record): bool => $record === null || $record->playing())
            ->action(static function (?Account $record): void {
                if ($record !== null && !$record->playing()) {
                    $record->delete();
                }
            });
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function getViewData(): array
    {
        $accounts = Account::query()
            ->with([
                'script',
                'logs' => static fn(HasMany $logs): HasMany => $logs->latest('id')->limit(self::LOG_LINES),
            ])
            ->orderBy('name')
            ->get();
        $playing = $accounts->filter(static fn(Account $account): bool => $account->playing());
        $off = $accounts->reject(static fn(Account $account): bool => $account->playing());

        return [
            'accounts' => match ($this->show) {
                'game' => $playing->values(),
                'off' => $off->values(),
                default => $accounts,
            },
            'counts' => ['all' => $accounts->count(), 'game' => $playing->count(), 'off' => $off->count()],
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('startIdle')
                ->label('Start all idle')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Start every idle bot?')
                ->modalDescription('Each character that is off logs in and enters the game.')
                ->modalSubmitActionLabel('Start')
                ->visible(static fn(): bool => Account::query()->get()->contains(
                    static fn(Account $account): bool => !$account->playing(),
                ))
                ->action(static function (): void {
                    foreach (Account::query()->get() as $account) {
                        $account->start();
                    }
                }),
            Action::make('addAccount')
                ->label('Add account')
                ->modalHeading('Add account')
                ->modalSubmitActionLabel('Add')
                ->model(Account::class)
                ->schema(AccountForm::creating(...))
                ->action(static function (array $data): void {
                    Account::query()->create($data);
                }),
        ];
    }
}
