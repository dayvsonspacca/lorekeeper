<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Core\Player\PlayerLevel;

final class Parameters
{
    public static function levelField(): TextInput
    {
        return TextInput::make('parameters.level')
            ->label('Level')
            ->prefixIcon(Heroicon::OutlinedArrowTrendingUp)
            ->numeric()
            ->integer()
            ->minValue(2)
            ->maxValue(100)
            ->helperText('The bot stops once the character reaches it.')
            ->required();
    }

    /** @param array<string, mixed> $parameters */
    public static function level(array $parameters): PlayerLevel
    {
        $level = is_numeric($parameters['level'] ?? null) ? (int) $parameters['level'] : 100;

        return new PlayerLevel(max(1, min(100, $level)));
    }
}
