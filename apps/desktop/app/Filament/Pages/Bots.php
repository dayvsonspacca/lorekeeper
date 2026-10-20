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
use Lorekeeper\Apps\Desktop\Bots\Realm;
use Lorekeeper\Apps\Desktop\Bots\TaskStatus;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\AccountResource;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Schemas\TaskForm;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotRunLog;
use Override;

final class Bots extends Page
{
    private const int LOG_LINES = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Bots';

    protected string $view = 'filament.pages.bots';

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
            ->first(static fn(Account $account): bool => $account->activeRun() !== null)
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

    public function resume(int $account): void
    {
        Account::query()->find($account)?->resume();
    }

    public function stopAction(): Action
    {
        return Action::make('stop')
            ->color('danger')
            ->outlined()
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

    public function addTaskAction(): Action
    {
        return Action::make('addTask')
            ->label('Add task')
            ->icon(Heroicon::OutlinedPlus)
            ->color('gray')
            ->size(Size::ExtraSmall)
            ->modalIcon(Heroicon::OutlinedQueueList)
            ->modalHeading('Add tasks')
            ->modalDescription(
                'The bot plays its tasks one after the other, in this order, and waits where it is in between.',
            )
            ->modalSubmitActionLabel('Add')
            ->schema([TaskForm::queue()])
            ->fillForm(TaskForm::blankQueue())
            ->action(static function (array $arguments, array $data): void {
                $run = BotRun::query()->find($arguments['run'] ?? 0);
                if ($run === null || !$run->status->isActive()) {
                    return;
                }

                TaskForm::addTo($run, $data);
            })
            ->successNotificationTitle('Tasks added');
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function getViewData(): array
    {
        $accounts = Account::query()
            ->with('latestRun')
            ->orderBy('name')
            ->get();

        return [
            'accounts' => $accounts,
            'now' => now()->getTimestamp(),
            'realm' => Realm::of($accounts->all(), now()),
            'detail' => $this->detail($accounts->firstWhere('id', $this->expanded)),
        ];
    }

    /** @return null|array<string, mixed> */
    private function detail(?Account $account): ?array
    {
        $run = $account?->latestRun;
        if ($run === null) {
            return null;
        }

        return [
            'run' => $run,
            'queue' => $run
                ->tasks()
                ->whereIn('status', [TaskStatus::Running, TaskStatus::Cancelling, TaskStatus::Pending])
                ->orderBy('id')
                ->get(),
            'logs' => $run
                ->logs()
                ->latest('id')
                ->limit(self::LOG_LINES)
                ->get()
                ->reverse()
                ->map(static fn(BotRunLog $log): array => [
                    'at' => $log->created_at->format('H:i:s'),
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
                ->modalDescription('Each character that is off logs in, enters the game and waits for tasks.')
                ->modalSubmitActionLabel('Start')
                ->visible(static fn(): bool => Account::query()->get()->contains(
                    static fn(Account $account): bool => $account->activeRun() === null,
                ))
                ->action(static function (): void {
                    foreach (Account::query()->get() as $account) {
                        $account->start();
                    }
                }),
            Action::make('addAccount')
                ->label('Add character')
                ->modalHeading('Add character')
                ->modalSubmitActionLabel('Add')
                ->schema(AccountResource::form(...))
                ->action(static function (array $data): void {
                    Account::query()->create($data);
                }),
        ];
    }
}
