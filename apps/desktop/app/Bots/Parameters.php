<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Lorekeeper\Apps\Desktop\Game\GameCatalog;
use Lorekeeper\Core\Player\PlayerLevel;

final class Parameters
{
    public static function mapField(): TextInput
    {
        return TextInput::make('parameters.map')
            ->label('Map')
            ->prefixIcon(Heroicon::OutlinedMap)
            ->datalist(static fn(GameCatalog $catalog): array => array_keys($catalog->maps()))
            ->placeholder('battleon')
            ->helperText('Any map name; the travel map ones are suggested. A room goes after a dash, like battleon-2.')
            ->regex('/^[A-Za-z0-9_-]+$/')
            ->maxLength(64)
            ->required();
    }

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

    public static function idsField(string $key, string $label, string $hint): TagsInput
    {
        return TagsInput::make("parameters.{$key}")
            ->label($label)
            ->placeholder('Type an id and press Enter')
            ->splitKeys([',', ' ', 'Tab'])
            ->nestedRecursiveRules(['integer', 'min:1'])
            ->helperText($hint)
            ->required();
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<positive-int>
     */
    public static function ids(array $parameters, string $key): array
    {
        $values = $parameters[$key] ?? [];

        return array_values(array_filter(
            array_map(static fn(mixed $id): int => is_numeric($id) ? (int) $id : 0, is_array($values) ? $values : []),
            static fn(int $id): bool => $id > 0,
        ));
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return non-empty-list<positive-int>
     */
    public static function requiredIds(array $parameters, string $key): array
    {
        $ids = self::ids($parameters, $key);
        if ($ids === []) {
            throw new InvalidArgumentException("The run has no {$key} to ask for.");
        }

        return $ids;
    }
}
