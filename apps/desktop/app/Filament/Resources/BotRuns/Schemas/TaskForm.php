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
use Lorekeeper\Apps\Desktop\Models\BotRun;

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
            ->itemLabel(static fn(array $state): ?string => self::chosen($state['script'] ?? null)?->getLabel());
    }

    /** @return array{tasks: list<array{script: null, parameters: array<string, null>}>} */
    public static function blankQueue(): array
    {
        return ['tasks' => [['script' => null, ...self::blank()]]];
    }

    /** @param array<string, mixed> $data */
    public static function addTo(BotRun $run, array $data): int
    {
        $added = 0;
        foreach ((array) ($data['tasks'] ?? []) as $task) {
            if (!is_array($task) || ($task['script'] ?? null) === null) {
                continue;
            }

            $run->tasks()->create(['script' => $task['script'], 'parameters' => $task['parameters'] ?? []]);
            $added++;
        }

        return $added;
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
