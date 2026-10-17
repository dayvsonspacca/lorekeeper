<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Lorekeeper\Apps\Desktop\Bots\RunStatus;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions\RunAgainAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Actions\StopRunAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages\CreateBotRun;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages\ListBotRuns;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Desktop\Livewire\RunLog;
use Lorekeeper\Apps\Desktop\Livewire\RunTasks;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Override;
use UnitEnum;

final class BotRunResource extends Resource
{
    protected static ?string $model = BotRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlay;

    protected static string|UnitEnum|null $navigationGroup = 'Bots';

    protected static ?string $navigationLabel = 'Runs';

    protected static ?string $modelLabel = 'run';

    protected static ?int $navigationSort = 2;

    #[Override]
    public static function getNavigationBadge(): ?string
    {
        $running = BotRun::query()->whereIn('status', [RunStatus::Running, RunStatus::Stopping])->count();

        return $running > 0 ? (string) $running : null;
    }

    #[Override]
    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('account_id')
                ->label('Character')
                ->relationship('account', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('The bot logs in, enters the game and waits for tasks.')
                ->columnSpanFull(),
        ]);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Run')
                ->icon(Heroicon::OutlinedPlay)
                ->schema([
                    TextEntry::make('account.name')->label('Character')->icon(Heroicon::OutlinedUser),
                    TextEntry::make('map')->icon(Heroicon::OutlinedMap)->placeholder('Not in the game yet'),
                    TextEntry::make('started_at')->dateTime()->placeholder('Not started'),
                    TextEntry::make('finished_at')->dateTime()->placeholder('—'),
                    TextEntry::make('result')
                        ->visible(static fn(BotRun $record): bool => $record->result !== null)
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->columnSpanFull(),
            Section::make('Tasks')
                ->icon(Heroicon::OutlinedQueueList)
                ->schema([Livewire::make(RunTasks::class, static fn(BotRun $record): array => ['run' => $record])])
                ->columnSpanFull(),
            Section::make('Log')
                ->icon(Heroicon::OutlinedCommandLine)
                ->schema([Livewire::make(RunLog::class, static fn(BotRun $record): array => ['run' => $record])])
                ->columnSpanFull(),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->color('gray'),
                TextColumn::make('account.name')
                    ->label('Character')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('map')->icon(Heroicon::OutlinedMap)->placeholder('—'),
                ViewColumn::make('current')->label('Doing now')->view('columns.current-task'),
                TextColumn::make('tasks_count')
                    ->counts('tasks')
                    ->label('Tasks')
                    ->color('gray'),
                TextColumn::make('duration')
                    ->state(static fn(BotRun $record): ?string => $record->duration())
                    ->placeholder('—')
                    ->color('gray'),
                TextColumn::make('result')
                    ->limit(40)
                    ->placeholder('—')
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(RunStatus::class)->multiple(),
                SelectFilter::make('account')->label('Character')->relationship('account', 'name'),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    StopRunAction::make(),
                    RunAgainAction::make(),
                    DeleteAction::make()->visible(static fn(BotRun $record): bool => !$record->status->isActive()),
                ]),
            ])
            ->toolbarActions([DeleteBulkAction::make()])
            ->emptyStateIcon(Heroicon::OutlinedPlay)
            ->emptyStateHeading('No runs yet')
            ->emptyStateDescription('Start a run on one of your accounts, then give it tasks.')
            ->poll('5s');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListBotRuns::route('/'),
            'create' => CreateBotRun::route('/create'),
            'view' => ViewBotRun::route('/{record}'),
        ];
    }
}
