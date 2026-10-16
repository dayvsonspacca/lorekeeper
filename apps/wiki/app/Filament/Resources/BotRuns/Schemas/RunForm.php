<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Filament\Resources\BotRuns\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Lorekeeper\Apps\Wiki\Bots\BotScript;

final class RunForm
{
    /** @return list<Component|Select> */
    public static function components(): array
    {
        return [
            Select::make('account_id')
                ->label('Character')
                ->relationship('account', 'name')
                ->searchable()
                ->preload()
                ->required(),
            ...self::forAccount(),
        ];
    }

    /** @return list<Component|Select> */
    public static function forAccount(): array
    {
        return [
            Select::make('script')
                ->options(BotScript::class)
                ->required()
                ->live()
                ->afterStateUpdated(static fn(Set $set): mixed => $set('parameters', []))
                ->helperText(static fn(Get $get): ?string => self::script($get)?->getDescription()),
            Section::make('Parameters')
                ->schema(static fn(Get $get): array => self::script($get)?->fields() ?? [])
                ->visible(static fn(Get $get): bool => self::script($get) !== null)
                ->columnSpanFull(),
        ];
    }

    private static function script(Get $get): ?BotScript
    {
        $script = $get('script');

        return $script instanceof BotScript ? $script : BotScript::tryFrom((string) $script);
    }
}
