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
use Lorekeeper\Apps\Desktop\Bots\Launcher\Launcher;
use Lorekeeper\Apps\Desktop\Filament\Schemas\AccountForm;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotLog;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Native\LocalTime;
use Override;

/**
 * @mago-expect lint:too-many-methods
 * @mago-expect lint:cyclomatic-complexity
 */
final class Accounts extends Page
{
    private const int LOG_LINES = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Accounts';

    protected string $view = 'filament.pages.accounts';

    public ?int $expanded = null;

    #[Override]
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function mount(): void
    {
        $this->expanded = Account::query()
            ->orderBy('name')
            ->get()
            ->first(static fn(Account $account): bool => $account->playing())
            ?->id;
    }

    public function toggle(int $account): void
    {
        $this->expanded = $this->expanded === $account ? null : $account;
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
            ->requiresConfirmation()
            ->modalHeading(
                static fn(array $arguments): string => (
                    'Stop '
                    . (Account::query()->find($arguments['account'] ?? 0)->name ?? 'this bot')
                    . '?'
                ),
            )
            ->modalDescription('The bot logs out of the game.')
            ->modalSubmitActionLabel('Stop')
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
            ->iconButton()
            ->color('gray')
            ->size(Size::Small)
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
            ->iconButton()
            ->color('gray')
            ->size(Size::Small)
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
            ->with('script')
            ->orderBy('name')
            ->get();

        return [
            'accounts' => $accounts,
            'now' => now()->getTimestamp(),
            'detail' => $this->detail($accounts->firstWhere('id', $this->expanded)),
        ];
    }

    /** @return null|array<string, mixed> */
    private function detail(?Account $account): ?array
    {
        if ($account === null) {
            return null;
        }

        return [
            'logs' => $account
                ->logs()
                ->latest('id')
                ->limit(self::LOG_LINES)
                ->get()
                ->reverse()
                ->map(static fn(BotLog $log): array => [
                    'at' => LocalTime::clock($log->created_at),
                    'message' => $log->message,
                    'level' => $log->level->value,
                ])
                ->values()
                ->all(),
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
