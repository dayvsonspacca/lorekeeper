<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Actions\StopRunAction;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\CreateBotRun;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ListBotRuns;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Pages\ViewBotRun;
use Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\RelationManagers\LogsRelationManager;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Override;

final class BotRunResource extends Resource
{
    protected static ?string $model = BotRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlay;

    protected static ?string $navigationLabel = 'Runs';

    protected static ?string $modelLabel = 'run';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('account_id')->relationship('account', 'name')->required(),
            Select::make('script')
                ->options(BotScript::class)
                ->default(BotScript::StayInMap)
                ->required(),
            TextInput::make('parameters.map')
                ->label('Map')
                ->default('battleon')
                ->required(),
        ]);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('account.name')->label('Character'),
            TextEntry::make('script'),
            TextEntry::make('parameters.map')->label('Map'),
            TextEntry::make('status')->badge(),
            TextEntry::make('result')->placeholder('—'),
            TextEntry::make('started_at')->dateTime()->placeholder('—'),
            TextEntry::make('finished_at')->dateTime()->placeholder('—'),
        ])->columns(4);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('account.name')->label('Character')->searchable(),
                TextColumn::make('script'),
                TextColumn::make('parameters.map')->label('Map'),
                TextColumn::make('status')->badge(),
                TextColumn::make('result')->placeholder('—'),
                TextColumn::make('started_at')->since()->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([ViewAction::make(), StopRunAction::make()])
            ->poll('5s');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [LogsRelationManager::class];
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
