<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\Accounts;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lorekeeper\Apps\Wiki\Filament\Resources\Accounts\Pages\ManageAccounts;
use Lorekeeper\Apps\Wiki\Models\Account;
use Override;

final class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Character')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->required(static fn(string $operation): bool => $operation === 'create')
                ->dehydrated(static fn(?string $state): bool => filled($state))
                ->helperText('Stored encrypted. Leave empty to keep the current one.'),
            TextInput::make('server')->placeholder('First open server')->maxLength(255),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Character')->searchable(),
            TextColumn::make('server')->placeholder('First open server'),
            TextColumn::make('runs_count')->counts('runs')->label('Runs'),
            TextColumn::make('created_at')->since(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    #[Override]
    public static function getPages(): array
    {
        return ['index' => ManageAccounts::route('/')];
    }
}
