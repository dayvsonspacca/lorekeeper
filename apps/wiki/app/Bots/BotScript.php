<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Lorekeeper\Apps\Wiki\Bots\Scripts\StayInMapScript;
use Lorekeeper\Apps\Wiki\Game\GameCatalog;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\World\Map\MapName;
use Override;

enum BotScript: string implements HasLabel, HasDescription
{
    case StayInMap = 'stay-in-map';

    #[Override]
    public function getLabel(): string
    {
        return match ($this) { self::StayInMap => 'Stay in map' };
    }

    #[Override]
    public function getDescription(): string
    {
        return match ($this) { self::StayInMap => 'Enters the game, goes to a map and stays there until stopped.' };
    }

    /** @return list<Component> */
    public function fields(): array
    {
        return match ($this) {
            self::StayInMap => [
                Select::make('parameters.map')
                    ->label('Map')
                    ->options(static fn(GameCatalog $catalog): array => $catalog->maps())
                    ->searchable()
                    ->required(),
            ],
        };
    }

    /** @return array<string, null> */
    public static function blankParameters(): array
    {
        $keys = array_merge(...array_map(static fn(self $script): array => array_keys(
            $script->defaults(),
        ), self::cases()));

        return array_fill_keys($keys, value: null);
    }

    /** @return non-empty-array<string, string> */
    public function defaults(): array
    {
        return match ($this) { self::StayInMap => ['map' => 'battleon'] };
    }

    /** @param array<string, string> $parameters */
    public function summary(array $parameters): string
    {
        return match ($this) { self::StayInMap => $parameters['map'] ?? '—' };
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return Script<object>
     */
    public function script(GameLogin $login, array $parameters): Script
    {
        return match ($this) {
            self::StayInMap => new StayInMapScript($login, new MapName($parameters['map'] ?? 'battleon')),
        };
    }
}
