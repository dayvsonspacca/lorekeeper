<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Lorekeeper\Client\Bot\Task\AskQuestsTask;
use Lorekeeper\Client\Bot\Task\GoToMapTask;
use Lorekeeper\Client\Bot\Task\LevelUpTask;
use Lorekeeper\Client\Bot\Task\OpenShopsTask;
use Lorekeeper\Client\Bot\Task\Task;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Override;

enum BotScript: string implements HasLabel, HasDescription
{
    case GoToMap = 'go-to-map';
    case AskQuests = 'ask-quests';
    case OpenShops = 'open-shops';
    case LevelUp = 'level-up';

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
            self::LevelUp => 'Level up',
        };
    }

    #[Override]
    public function getDescription(): string
    {
        return match ($this) {
            self::GoToMap => 'Goes to a map; the bot then waits there for the next task.',
            self::AskQuests => 'Goes to a map if needed and asks for the given quests, ten at a time.',
            self::OpenShops => 'Goes to a map if needed and opens the given shops one by one.',
            self::LevelUp
                => 'Fights until the character reaches the level: oaklore, then swordhavenundead, then icestormarena.',
        };
    }

    /** @return non-empty-array<string, mixed> */
    public function defaults(): array
    {
        return match ($this) {
            self::GoToMap => ['map' => 'battleon'],
            self::AskQuests => ['map' => 'battleon', 'quests' => []],
            self::OpenShops => ['map' => 'battleon', 'shops' => []],
            self::LevelUp => ['level' => 100],
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
            self::LevelUp => [Parameters::levelField()],
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
            self::LevelUp => 'to level ' . Parameters::level($parameters)->value,
        };
    }

    /** @param array<string, mixed> $parameters */
    public function task(array $parameters): Task
    {
        $map = new MapName(
            is_string($parameters['map'] ?? null) && $parameters['map'] !== '' ? $parameters['map'] : 'battleon',
        );

        return match ($this) {
            self::GoToMap => new GoToMapTask($map),
            self::AskQuests => new AskQuestsTask($map, array_map(
                static fn(int $id): QuestId => new QuestId($id),
                Parameters::requiredIds($parameters, 'quests'),
            )),
            self::OpenShops => new OpenShopsTask($map, array_map(
                static fn(int $id): ShopId => new ShopId($id),
                Parameters::requiredIds($parameters, 'shops'),
            )),
            self::LevelUp => new LevelUpTask(Parameters::level($parameters)),
        };
    }
}
