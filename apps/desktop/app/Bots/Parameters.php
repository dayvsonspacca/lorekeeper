<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
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

    public const array ENHANCEMENT_TYPES = ['Fighter', 'Thief', 'Hybrid', 'Wizard', 'Healer', 'SpellBreaker', 'Lucky'];

    public static function enhancementTypeField(): Select
    {
        return Select::make('parameters.type')
            ->label('Enhancement')
            ->options(array_combine(self::ENHANCEMENT_TYPES, self::ENHANCEMENT_TYPES))
            ->helperText(
                'Each equipped weapon, class, helm and cape gets the strongest one the character can use and afford.',
            )
            ->required();
    }

    /** @param array<string, mixed> $parameters */
    public static function enhancementType(array $parameters): EnhancementType
    {
        $chosen = $parameters['type'] ?? null;
        foreach (EnhancementType::cases() as $type) {
            if ($type->name === $chosen && in_array($chosen, self::ENHANCEMENT_TYPES, strict: true)) {
                return $type;
            }
        }

        return EnhancementType::Lucky;
    }
}
