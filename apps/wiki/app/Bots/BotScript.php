<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Bots;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Lorekeeper\Apps\Wiki\Bots\Scripts\AskQuestsScript;
use Lorekeeper\Apps\Wiki\Bots\Scripts\GoToMapScript;
use Lorekeeper\Apps\Wiki\Bots\Scripts\OpenShopsScript;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Override;

enum BotScript: string implements HasLabel, HasDescription
{
    case GoToMap = 'go-to-map';
    case AskQuests = 'ask-quests';
    case OpenShops = 'open-shops';

    /** @return array<string, null> */
    public static function blankParameters(): array
    {
        $keys = array_merge(...array_map(static fn(self $script): array => array_keys(
            $script->defaults(),
        ), self::cases()));

        return array_fill_keys($keys, value: null);
    }

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::GoToMap => 'Go to map',
            self::AskQuests => 'Ask for quests',
            self::OpenShops => 'Open shops',
        };
    }

    #[Override]
    public function getDescription(): string
    {
        return match ($this) {
            self::GoToMap => 'Goes to a map; the bot then waits there for the next task.',
            self::AskQuests => 'Goes to a map if needed and asks for the given quests, ten at a time.',
            self::OpenShops => 'Goes to a map if needed and opens the given shops one by one.',
        };
    }

    /** @return non-empty-array<string, mixed> */
    public function defaults(): array
    {
        return match ($this) {
            self::GoToMap => ['map' => 'battleon'],
            self::AskQuests => ['map' => 'battleon', 'quests' => []],
            self::OpenShops => ['map' => 'battleon', 'shops' => []],
        };
    }

    /** @return list<Component|Field> */
    public function fields(): array
    {
        return match ($this) {
            self::GoToMap => [Parameters::mapField()],
            self::AskQuests => [
                Parameters::mapField(),
                Parameters::idsField('quests', 'Quest ids', 'Only ask for quests you have seen.'),
            ],
            self::OpenShops => [
                Parameters::mapField(),
                Parameters::idsField(
                    'shops',
                    'Shop ids',
                    'Some shops drop the connection; the run then ends disconnected.',
                ),
            ],
        };
    }

    /** @param array<string, mixed> $parameters */
    public function summary(array $parameters): string
    {
        $map = is_string($parameters['map'] ?? null) ? $parameters['map'] : '—';

        return match ($this) {
            self::GoToMap => $map,
            self::AskQuests => "{$map} · " . count(Parameters::ids($parameters, 'quests')) . ' quests',
            self::OpenShops => "{$map} · " . count(Parameters::ids($parameters, 'shops')) . ' shops',
        };
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return Script<object>
     */
    public function script(PlayerName $player, Position $here, array $parameters): Script
    {
        $map = new MapName(
            is_string($parameters['map'] ?? null) && $parameters['map'] !== '' ? $parameters['map'] : 'battleon',
        );

        return match ($this) {
            self::GoToMap => new GoToMapScript($player, $here, $map),
            self::AskQuests => new AskQuestsScript(
                $player,
                $here,
                $map,
                array_map(
                    static fn(int $id): QuestId => new QuestId($id),
                    Parameters::requiredIds($parameters, 'quests'),
                ),
            ),
            self::OpenShops => new OpenShopsScript(
                $player,
                $here,
                $map,
                array_map(static fn(int $id): ShopId => new ShopId($id), Parameters::requiredIds($parameters, 'shops')),
            ),
        };
    }
}
