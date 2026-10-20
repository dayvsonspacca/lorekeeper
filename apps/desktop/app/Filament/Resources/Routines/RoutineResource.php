<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\Routines;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lorekeeper\Apps\Desktop\Bots\Parameters;
use Lorekeeper\Apps\Desktop\Bots\RoutineSteps;
use Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Schemas\TaskForm;
use Lorekeeper\Apps\Desktop\Filament\Resources\Routines\Pages\ManageRoutines;
use Lorekeeper\Apps\Desktop\Models\Routine;
use Override;

final class RoutineResource extends Resource
{
    protected static ?string $model = Routine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->placeholder('Go to level 100')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->columnSpanFull(),
            Actions::make([self::levelStepsAction()])->columnSpanFull(),
            TaskForm::queue()
                ->label('Tasks')
                ->collapsed()
                ->columnSpanFull(),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('bold')->searchable(),
                TextColumn::make('tasks')
                    ->label('Tasks')
                    ->state(static fn(Routine $record): int => count($record->steps()))
                    ->color('gray'),
                TextColumn::make('updated_at')
                    ->label('Changed')
                    ->since()
                    ->color('gray'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->modalHeading(static fn(Routine $record): string => "Edit {$record->name}"),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedQueueList)
            ->emptyStateHeading('No routines yet')
            ->emptyStateDescription('Save a list of tasks once and queue it on any character.');
    }

    #[Override]
    public static function getPages(): array
    {
        return ['index' => ManageRoutines::route('/')];
    }

    private static function levelStepsAction(): Action
    {
        return Action::make('levelSteps')
            ->label('Fill with level steps')
            ->icon(Heroicon::OutlinedArrowTrendingUp)
            ->color('gray')
            ->modalHeading('Level up in steps')
            ->modalDescription('Writes the tasks for you; the routine stays a plain list you can still change.')
            ->modalSubmitActionLabel('Fill')
            ->schema([
                TextInput::make('from')
                    ->label('First stop')
                    ->numeric()
                    ->integer()
                    ->minValue(2)
                    ->maxValue(100)
                    ->default(5)
                    ->required(),
                TextInput::make('to')
                    ->label('Last stop')
                    ->numeric()
                    ->integer()
                    ->minValue(2)
                    ->maxValue(100)
                    ->default(100)
                    ->required(),
                TextInput::make('every')
                    ->label('Levels per step')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(99)
                    ->default(5)
                    ->required(),
                Select::make('enhancement')
                    ->label('Enhance after each step')
                    ->options(array_combine(Parameters::ENHANCEMENT_TYPES, Parameters::ENHANCEMENT_TYPES))
                    ->placeholder('Do not enhance'),
            ])
            ->action(static function (array $data, Set $set): void {
                $set('tasks', RoutineSteps::levelUp(
                    (int) $data['from'],
                    (int) $data['to'],
                    (int) $data['every'],
                    is_string($data['enhancement'] ?? null) ? $data['enhancement'] : null,
                ));
            });
    }
}
