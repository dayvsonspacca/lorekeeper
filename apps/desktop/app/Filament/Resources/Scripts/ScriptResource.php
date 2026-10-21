<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Scripts;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Actions\ExportScriptAction;
use Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Pages\DrawScript;
use Lorekeeper\Apps\Desktop\Filament\Resources\Scripts\Pages\ManageScripts;
use Lorekeeper\Apps\Desktop\Models\Script;
use Override;

final class ScriptResource extends Resource
{
    protected static ?string $model = Script::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = -1;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(120)
                ->autofocus(),
            TextInput::make('author')->maxLength(120)->placeholder('Who drew it'),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->description(static fn(Script $record): string => $record->author === null
                        ? 'No author'
                        : "by {$record->author}")
                    ->searchable(),
                TextColumn::make('nodes')
                    ->state(static fn(Script $record): string => match ($record->nodes()) {
                        1 => '1 node',
                        default => "{$record->nodes()} nodes",
                    })
                    ->color('gray'),
                TextColumn::make('state')
                    ->badge()
                    ->state(self::state(...))
                    ->color(static fn(string $state): string => match ($state) {
                        'Ready' => 'success',
                        'Empty' => 'gray',
                        default => 'warning',
                    })
                    ->tooltip(static fn(Script $record): ?string => $record->nodes() === 0 || $record->problems() === []
                        ? null
                        : implode("\n", array_slice($record->problems(), offset: 0, length: 5))),
                TextColumn::make('updated_at')
                    ->label('Changed')
                    ->since()
                    ->color('gray'),
            ])
            ->defaultSort('name')
            ->recordUrl(static fn(Script $record): string => self::getUrl('draw', ['record' => $record]))
            ->recordActions([
                ExportScriptAction::make(),
                ActionGroup::make([
                    EditAction::make()
                        ->label('Rename')
                        ->modalHeading(static fn(Script $record): string => "Rename {$record->name}"),
                    ReplicateAction::make()
                        ->label('Duplicate')
                        ->modalHeading('Duplicate script')
                        ->modalSubmitActionLabel('Duplicate')
                        ->schema(self::form(...))
                        ->fillForm(static fn(Script $record): array => [
                            'name' => "{$record->name} (copy)",
                            'author' => $record->author,
                        ])
                        ->successNotificationTitle('Duplicated'),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedRectangleGroup)
            ->emptyStateHeading('No scripts yet')
            ->emptyStateDescription('Draw a new one or import a file someone shared.');
    }

    #[Override]
    public static function getPages(): array
    {
        return ['index' => ManageScripts::route('/'), 'draw' => DrawScript::route('/{record}')];
    }

    private static function state(Script $record): string
    {
        $problems = count($record->problems());

        return match (true) {
            $record->nodes() === 0 => 'Empty',
            $problems === 0 => 'Ready',
            $problems === 1 => '1 problem',
            default => "{$problems} problems",
        };
    }
}
