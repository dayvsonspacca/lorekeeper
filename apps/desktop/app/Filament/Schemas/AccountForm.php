<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Apps\Desktop\Game\GameCatalog;

final class AccountForm
{
    public static function creating(Schema $schema): Schema
    {
        return self::make($schema, self::password()->required()->helperText('Stored encrypted.'));
    }

    public static function editing(Schema $schema): Schema
    {
        return self::make(
            $schema,
            self::password()->helperText('Stored encrypted. Leave empty to keep the current one.'),
        );
    }

    private static function make(Schema $schema, TextInput $keyField): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Character')
                ->prefixIcon(Heroicon::OutlinedUser)
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            $keyField,
            Select::make('server')
                ->options(static fn(GameCatalog $catalog): array => $catalog->servers())
                ->searchable()
                ->placeholder('First open server')
                ->prefixIcon(Heroicon::OutlinedServerStack)
                ->helperText('Leave empty to join the first server that is online and open to everyone.')
                ->columnSpanFull(),
        ])->columns(2);
    }

    private static function password(): TextInput
    {
        return TextInput::make('password')
            ->password()
            ->revealable()
            ->prefixIcon(Heroicon::OutlinedKey)
            ->dehydrated(static fn(?string $state): bool => filled($state));
    }
}
