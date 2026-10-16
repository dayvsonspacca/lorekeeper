<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

final class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('At')->dateTime('H:i:s'),
                TextColumn::make('message')->fontFamily(FontFamily::Mono),
            ])
            ->defaultSort('id', 'desc')
            ->poll('2s');
    }
}
