<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\Check;
use Lorekeeper\Bot\Steps\Checks\Holding;
use Lorekeeper\Bot\Steps\Checks\LevelAtLeast;
use Lorekeeper\Bot\Steps\Checks\QuestDone;
use Lorekeeper\Bot\Steps\Fights\FightFor;
use Lorekeeper\Bot\Steps\Fights\FightToLevel;
use Lorekeeper\Bot\Steps\Fights\Hunt;
use Lorekeeper\Bot\Steps\Gear\Enhance;
use Lorekeeper\Bot\Steps\Moves\JoinMap;
use Lorekeeper\Bot\Steps\Moves\MoveTo;
use Lorekeeper\Bot\Steps\Pauses\Rest;
use Lorekeeper\Bot\Steps\Pauses\Wait;
use Lorekeeper\Bot\Steps\Quests\AcceptQuest;
use Lorekeeper\Bot\Steps\Quests\TurnInQuest;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Quest\QuestId;
use Psl\DateTime\Duration;

final readonly class Steps
{
    public const array KINDS = [
        'join' => ['map'],
        'move' => ['cell', 'pad'],
        'hunt' => ['cell', 'pad', 'monster', 'item', 'quantity', 'minutes'],
        'fight-to-level' => ['cell', 'pad', 'monster', 'level'],
        'fight-for' => ['cell', 'pad', 'monster', 'minutes'],
        'accept-quest' => ['quest'],
        'turn-in-quest' => ['quest', 'reward'],
        'enhance' => ['type'],
        'rest' => [],
        'wait' => ['seconds'],
        'quest-done' => ['quest'],
        'holding' => ['item', 'quantity'],
        'level' => ['level'],
    ];

    /** @return null|key-of<self::KINDS> */
    public static function kind(mixed $kind): ?string
    {
        return is_string($kind) && array_key_exists($kind, self::KINDS) ? $kind : null;
    }

    /** @param key-of<self::KINDS> $kind */
    public static function read(string $kind, Fields $fields): null|Action|Check
    {
        return match ($kind) {
            'join' => self::all(
                $fields,
                [$fields->text('map')],
                static fn(string $map): Action => new JoinMap(new MapName($map)),
            ),
            'move' => self::all($fields, [self::spot($fields)], static fn(MapSpot $spot): Action => new MoveTo($spot)),
            'hunt' => self::all(
                $fields,
                [
                    self::spot($fields),
                    self::monster($fields),
                    $fields->number('item'),
                    $fields->number('quantity'),
                    $fields->number('minutes'),
                ],
                static fn(
                    MapSpot $spot,
                    ?MonsterName $monster,
                    int $item,
                    int $quantity,
                    int $minutes,
                ): Action => new Hunt(
                    $spot,
                    $monster,
                    new ItemId($item),
                    new Quantity($quantity),
                    Duration::minutes($minutes),
                ),
            ),
            'fight-to-level' => self::all(
                $fields,
                [self::spot($fields), self::monster($fields), $fields->number('level', most: 100)],
                static fn(MapSpot $spot, ?MonsterName $monster, int $level): Action => new FightToLevel(
                    $spot,
                    $monster,
                    new PlayerLevel($level),
                ),
            ),
            'fight-for' => self::all(
                $fields,
                [self::spot($fields), self::monster($fields), $fields->number('minutes')],
                static fn(MapSpot $spot, ?MonsterName $monster, int $minutes): Action => new FightFor(
                    $spot,
                    $monster,
                    Duration::minutes($minutes),
                ),
            ),
            'accept-quest' => self::all(
                $fields,
                [$fields->number('quest')],
                static fn(int $quest): Action => new AcceptQuest(new QuestId($quest)),
            ),
            'turn-in-quest' => self::all(
                $fields,
                [$fields->number('quest'), $fields->optionalNumber('reward')],
                static fn(int $quest, ?int $reward): Action => new TurnInQuest(
                    new QuestId($quest),
                    $reward === null ? null : new ItemId($reward),
                ),
            ),
            'enhance' => self::all(
                $fields,
                [$fields->choice('type', self::enhancements())],
                static fn(EnhancementType $type): Action => new Enhance($type),
            ),
            'rest' => new Rest(),
            'wait' => self::all(
                $fields,
                [$fields->number('seconds')],
                static fn(int $seconds): Action => new Wait(Duration::seconds($seconds)),
            ),
            'quest-done' => self::all(
                $fields,
                [$fields->number('quest')],
                static fn(int $quest): Check => new QuestDone(new QuestId($quest)),
            ),
            'holding' => self::all(
                $fields,
                [$fields->number('item'), $fields->number('quantity')],
                static fn(int $item, int $quantity): Check => new Holding(new ItemId($item), new Quantity($quantity)),
            ),
            'level' => self::all(
                $fields,
                [$fields->number('level', most: 100)],
                static fn(int $level): Check => new LevelAtLeast(new PlayerLevel($level)),
            ),
        };
    }

    /** @return array<string, int|string> */
    public static function write(Action|Check $step): array
    {
        return match (true) {
            $step instanceof JoinMap => ['do' => 'join', 'map' => $step->map->value],
            $step instanceof MoveTo => ['do' => 'move', ...self::place($step->spot, null)],
            $step instanceof Hunt => [
                'do' => 'hunt',
                ...self::place($step->spot, $step->monster),
                'item' => $step->item->value,
                'quantity' => $step->quantity->value,
                'minutes' => (int) $step->limit->getTotalMinutes(),
            ],
            $step instanceof FightToLevel => [
                'do' => 'fight-to-level',
                ...self::place($step->spot, $step->monster),
                'level' => $step->level->value,
            ],
            $step instanceof FightFor => [
                'do' => 'fight-for',
                ...self::place($step->spot, $step->monster),
                'minutes' => (int) $step->time->getTotalMinutes(),
            ],
            $step instanceof AcceptQuest => ['do' => 'accept-quest', 'quest' => $step->quest->value],
            $step instanceof TurnInQuest => [
                'do' => 'turn-in-quest',
                'quest' => $step->quest->value,
                ...($step->reward === null ? [] : ['reward' => $step->reward->value]),
            ],
            $step instanceof Enhance => ['do' => 'enhance', 'type' => $step->type->name],
            $step instanceof Wait => ['do' => 'wait', 'seconds' => (int) $step->time->getTotalSeconds()],
            $step instanceof QuestDone => ['do' => 'quest-done', 'quest' => $step->quest->value],
            $step instanceof Holding => [
                'do' => 'holding',
                'item' => $step->item->value,
                'quantity' => $step->quantity->value,
            ],
            $step instanceof LevelAtLeast => ['do' => 'level', 'level' => $step->level->value],
            default => ['do' => 'rest'],
        };
    }

    /**
     * @param list<mixed> $values
     * @param callable(mixed...): (Action|Check) $make
     */
    private static function all(Fields $fields, array $values, callable $make): null|Action|Check
    {
        return $fields->problems === [] ? $make(...$values) : null;
    }

    private static function spot(Fields $fields): ?MapSpot
    {
        $cell = $fields->text('cell');
        $pad = $fields->choice('pad', MapPad::cases(), MapPad::Left);

        return $cell === null || $pad === null ? null : new MapSpot(new MapCell($cell), $pad);
    }

    private static function monster(Fields $fields): ?MonsterName
    {
        $monster = $fields->optionalText('monster');

        return $monster === null ? null : new MonsterName($monster);
    }

    /** @return list<EnhancementType> */
    private static function enhancements(): array
    {
        return array_values(array_filter(
            EnhancementType::cases(),
            static fn(EnhancementType $type): bool => $type !== EnhancementType::Unhandled,
        ));
    }

    /** @return array<string, string> */
    private static function place(MapSpot $spot, ?MonsterName $monster): array
    {
        return [
            'cell' => $spot->cell->value,
            'pad' => $spot->pad->name,
            ...($monster === null ? [] : ['monster' => $monster->value]),
        ];
    }
}
