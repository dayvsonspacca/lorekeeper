<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\Players;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lorekeeper\Apps\Wiki\Filament\Resources\Players\Pages\ListPlayers;
use Lorekeeper\Apps\Wiki\Models\Player;
use Override;
use UnitEnum;

final class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Game';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Player')
                    ->weight('bold')
                    ->icon(Heroicon::OutlinedUser)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('aqw_id')
                    ->label('AQW id')
                    ->placeholder('Unknown')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('First seen')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->emptyStateHeading('No players yet')
            ->emptyStateDescription('Players show up here as the bots meet them in the game.')
            ->poll('10s');
    }

    #[Override]
    public static function getPages(): array
    {
        return ['index' => ListPlayers::route('/')];
    }
}
