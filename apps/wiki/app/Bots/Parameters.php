<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use InvalidArgumentException;
use Lorekeeper\Apps\Wiki\Game\GameCatalog;

final class Parameters
{
    public static function mapField(): Select
    {
        return Select::make('parameters.map')
            ->label('Map')
            ->options(static fn(GameCatalog $catalog): array => $catalog->maps())
            ->searchable()
            ->required();
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
