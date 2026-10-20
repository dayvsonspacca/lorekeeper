<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\TaskPlan;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\Routine;

final class TaskForm
{
    /** @return list<Component|Select> */
    public static function components(): array
    {
        return [
            Select::make('script')
                ->options(BotScript::class)
                ->required()
                ->live()
                ->afterStateUpdated(static fn(Get $get, Set $set): mixed => $set(
                    'parameters',
                    self::script($get)?->defaults(),
                ))
                ->helperText(static fn(Get $get): ?string => self::script($get)?->getDescription()),
            Section::make('Parameters')
                ->compact()
                ->schema(static fn(Get $get): array => self::script($get)?->fields() ?? [])
                ->visible(static fn(Get $get): bool => (self::script($get)?->fields() ?? []) !== [])
                ->columnSpanFull(),
        ];
    }

    /** @return array{parameters: array<string, null>} */
    public static function blank(): array
    {
        return ['parameters' => BotScript::blankParameters()];
    }

    public static function queue(): Repeater
    {
        return Repeater::make('tasks')
            ->hiddenLabel()
            ->schema(self::components())
            ->minItems(1)
            ->defaultItems(1)
            ->reorderable()
            ->addActionLabel('Add another task')
            ->itemLabel(self::label(...));
    }

    public static function scrolledQueue(): Repeater
    {
        return self::queue()
            ->collapsed(static fn(Get $get): bool => filled($get('routine')))
            ->extraAttributes(['class' => 'lk-task-list']);
    }

    public static function routine(): Select
    {
        return Select::make('routine')
            ->label('Routine')
            ->options(
                static fn(): array => Routine::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all(),
            )
            ->placeholder('Pick a routine to fill the list')
            ->helperText('Its tasks fill the list below, where you can still change them.')
            ->live()
            ->dehydrated(false)
            ->afterStateUpdated(static function (mixed $state, Set $set): void {
                $routine = Routine::query()->find($state);
                if ($routine instanceof Routine) {
                    $set('tasks', $routine->steps());
                }
            });
    }

    /** @return array{tasks: list<array{script: null, parameters: array<string, null>}>} */
    public static function blankQueue(): array
    {
        return ['tasks' => [['script' => null, ...self::blank()]]];
    }

    /** @param array<string, mixed> $data */
    public static function addTo(BotRun $run, array $data): int
    {
        $planned = TaskPlan::trimmed(TaskPlan::listed((array) ($data['tasks'] ?? [])), $run->account->level);
        foreach ($planned as $task) {
            $run->tasks()->create(['script' => $task['script'], 'parameters' => $task['parameters']]);
        }

        return count($planned);
    }

    /** @param array<array-key, mixed> $state */
    private static function label(array $state): ?string
    {
        $script = self::chosen($state['script'] ?? null);
        $parameters = $state['parameters'] ?? [];

        return $script === null
            ? null
            : "{$script->getLabel()} · {$script->summary(is_array($parameters) ? $parameters : [])}";
    }

    private static function script(Get $get): ?BotScript
    {
        return self::chosen($get('script'));
    }

    private static function chosen(mixed $script): ?BotScript
    {
        return match (true) {
            $script instanceof BotScript => $script,
            is_string($script) => BotScript::tryFrom($script),
            default => null,
        };
    }
}
