<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Accounts;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Actions\OpenRunAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Actions\StartRunAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Desktop\Game\GameCatalog;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Override;

final class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Character')
                ->prefixIcon(Heroicon::OutlinedUser)
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->prefixIcon(Heroicon::OutlinedKey)
                ->required(static fn(string $operation): bool => $operation === 'create')
                ->dehydrated(static fn(?string $state): bool => filled($state))
                ->helperText(static fn(string $operation): string => $operation === 'create'
                    ? 'Stored encrypted.'
                    : 'Stored encrypted. Leave empty to keep the current one.'),
            Select::make('server')
                ->options(static fn(GameCatalog $catalog): array => $catalog->servers())
                ->searchable()
                ->placeholder('First open server')
                ->prefixIcon(Heroicon::OutlinedServerStack)
                ->helperText('Leave empty to join the first server that is online and open to everyone.')
                ->columnSpanFull(),
        ])->columns(2);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Character')
                    ->weight('bold')
                    ->icon(Heroicon::OutlinedUser)
                    ->description(static fn(Account $record): string => $record->server ?? 'First open server')
                    ->searchable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->placeholder('—')
                    ->color('gray'),
                TextColumn::make('latestRun.status')
                    ->label('Bot')
                    ->badge()
                    ->placeholder('Never ran'),
                TextColumn::make('latestRun.map')
                    ->label('Map')
                    ->icon(Heroicon::OutlinedMap)
                    ->placeholder('—'),
                ViewColumn::make('current')
                    ->label('Doing now')
                    ->state(static fn(Account $record): ?BotRun => $record->latestRun)
                    ->view('columns.current-task'),
                TextColumn::make('runs_count')
                    ->counts('runs')
                    ->label('Runs')
                    ->color('gray'),
                TextColumn::make('latestRun.created_at')
                    ->label('Last played')
                    ->since()
                    ->placeholder('—'),
            ])
            ->recordActions([
                StartRunAction::make(),
                OpenRunAction::make(),
                ActionGroup::make([
                    EditAction::make()->modalHeading(static fn(Account $record): string => "Edit {$record->name}"),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedUserGroup)
            ->emptyStateHeading('No accounts yet')
            ->emptyStateDescription('Add the AQW characters your bots will play.')
            ->poll('2s');
    }

    #[Override]
    public static function getPages(): array
    {
        return ['index' => ManageAccounts::route('/')];
    }
}
