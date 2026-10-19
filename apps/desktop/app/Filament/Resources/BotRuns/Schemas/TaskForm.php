<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Lorekeeper\Apps\Desktop\Bots\BotScript;

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

    private static function script(Get $get): ?BotScript
    {
        $script = $get('script');

        return $script instanceof BotScript ? $script : BotScript::tryFrom((string) $script);
    }
}
