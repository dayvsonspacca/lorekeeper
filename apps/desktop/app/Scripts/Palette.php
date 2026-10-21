<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\World\Map\MapPad;

final readonly class Palette
{
    public const array GROUPS = [
        'Move' => ['join', 'move'],
        'Fight' => ['hunt', 'fight-to-level', 'fight-for'],
        'Quests' => ['accept-quest', 'turn-in-quest'],
        'Gear' => ['enhance'],
        'Pause' => ['rest', 'wait'],
        'Checks' => ['quest-done', 'holding', 'level'],
    ];

    /** @return array<string, array{label: string, icon: string, check: bool, summary: string, fields: list<array<string, mixed>>}> */
    public static function kinds(): array
    {
        $cell = self::text('cell', 'Cell', 'Enter');
        $pad = self::choice(
            'pad',
            'Pad',
            array_map(static fn(MapPad $pad): string => $pad->name, MapPad::cases()),
            'Left',
        );
        $monster = self::text('monster', 'Monster', 'Any monster in the cell', optional: true);

        return [
            'join' => self::kind('Join map', 'heroicon-o-map', '{map}', [self::text('map', 'Map', 'battleon')]),
            'move' => self::kind('Move to cell', 'heroicon-o-arrows-pointing-out', '{cell} · {pad}', [$cell, $pad]),
            'hunt' => self::kind('Hunt for item', 'heroicon-o-bolt', 'item {item} ×{quantity} · {cell}', [
                $cell,
                $pad,
                $monster,
                self::number('item', 'Item ID'),
                self::number('quantity', 'Quantity'),
                self::number('minutes', 'Give up after (minutes)'),
            ]),
            'fight-to-level' => self::kind('Fight to level', 'heroicon-o-arrow-trending-up', 'level {level} · {cell}', [
                $cell,
                $pad,
                $monster,
                self::number('level', 'Level', most: 100),
            ]),
            'fight-for' => self::kind('Fight for a while', 'heroicon-o-clock', '{minutes} min · {cell}', [
                $cell,
                $pad,
                $monster,
                self::number('minutes', 'Minutes'),
            ]),
            'accept-quest' => self::kind('Accept quest', 'heroicon-o-clipboard-document-list', 'quest {quest}', [
                self::number('quest', 'Quest ID'),
            ]),
            'turn-in-quest' => self::kind('Turn in quest', 'heroicon-o-gift', 'quest {quest}', [
                self::number('quest', 'Quest ID'),
                self::number('reward', 'Reward item ID', optional: true),
            ]),
            'enhance' => self::kind('Enhance gear', 'heroicon-o-sparkles', '{type}', [
                self::choice('type', 'Enhancement', self::enhancements(), null),
            ]),
            'rest' => self::kind('Rest', 'heroicon-o-heart', 'until HP and MP are full', []),
            'wait' => self::kind('Wait', 'heroicon-o-pause', '{seconds}s', [self::number('seconds', 'Seconds')]),
            'quest-done' => self::kind(
                'Quest done today?',
                'heroicon-o-check-badge',
                'quest {quest}',
                [
                    self::number('quest', 'Quest ID'),
                ],
                check: true,
            ),
            'holding' => self::kind(
                'Holding item?',
                'heroicon-o-archive-box',
                'item {item} ×{quantity}',
                [
                    self::number('item', 'Item ID'),
                    self::number('quantity', 'Quantity'),
                ],
                check: true,
            ),
            'level' => self::kind(
                'Level reached?',
                'heroicon-o-chart-bar',
                '{level} or higher',
                [
                    self::number('level', 'Level', most: 100),
                ],
                check: true,
            ),
        ];
    }

    /**
     * @param list<array<string, mixed>> $fields
     *
     * @return array{label: string, icon: string, check: bool, summary: string, fields: list<array<string, mixed>>}
     */
    private static function kind(
        string $label,
        string $icon,
        string $summary,
        array $fields,
        bool $check = false,
    ): array {
        return ['label' => $label, 'icon' => $icon, 'check' => $check, 'summary' => $summary, 'fields' => $fields];
    }

    /** @return array<string, mixed> */
    private static function text(string $name, string $label, string $placeholder, bool $optional = false): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => 'text',
            'placeholder' => $placeholder,
            'optional' => $optional,
        ];
    }

    /** @return array<string, mixed> */
    private static function number(string $name, string $label, ?int $most = null, bool $optional = false): array
    {
        return ['name' => $name, 'label' => $label, 'type' => 'number', 'most' => $most, 'optional' => $optional];
    }

    /**
     * @param list<string> $options
     *
     * @return array<string, mixed>
     */
    private static function choice(string $name, string $label, array $options, ?string $otherwise): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => 'choice',
            'options' => $options,
            'otherwise' => $otherwise,
            'optional' => false,
        ];
    }

    /** @return list<string> */
    private static function enhancements(): array
    {
        return array_values(array_map(
            static fn(EnhancementType $type): string => $type->name,
            array_filter(
                EnhancementType::cases(),
                static fn(EnhancementType $type): bool => $type !== EnhancementType::Unhandled,
            ),
        ));
    }
}
