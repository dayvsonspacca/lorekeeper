<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Core\Attribute\Stat\Boost\Boost;
use Lorekeeper\Core\Attribute\Stat\Haste;
use Lorekeeper\Core\Attribute\Stat\HealingIntake;
use Lorekeeper\Core\Attribute\Stat\Primary\PrimaryAttribute;
use Lorekeeper\Core\Attribute\Stat\Resistance\Resistance;
use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Attribute\Stat\Stats;
use Lorekeeper\Core\Support\NonNegativeInteger;

/**
 * @psalm-type Row = array{group: string, name: string, shown: string, about: string}
 */
final readonly class StatSheet
{
    private const array PANEL_ORDER = [
        'Attack Power',
        'Spell Power',
        'Hit Chance',
        'Haste',
        'Critical Chance',
        'Critical Multiplier',
        'Evasion',
    ];

    /** @return list<Row> */
    public static function of(Stats $stats): array
    {
        $rows = [];
        foreach (self::all($stats) as $stat) {
            $rows[] = [
                'group' => self::group($stat),
                'name' => self::name($stat),
                'shown' => self::shown($stat),
                'about' => $stat->describe()->value,
            ];
        }

        usort($rows, static fn(array $a, array $b): int => self::place($a) <=> self::place($b));

        return $rows;
    }

    /** @param Row $row */
    private static function place(array $row): int
    {
        $combat = array_search($row['name'], self::PANEL_ORDER, strict: true);

        return match ($row['group']) {
            'Attributes' => 0,
            'Combat' => 100 + (int) $combat,
            default => 200,
        };
    }

    /** @return list<Stat> */
    private static function all(object $values): array
    {
        $stats = [];
        foreach (get_object_vars($values) as $value) {
            $stats = [
                ...$stats,
                ...match (true) {
                    $value instanceof Stat => [$value],
                    is_object($value) => self::all($value),
                    default => [],
                },
            ];
        }

        return $stats;
    }

    private static function group(Stat $stat): string
    {
        return match (true) {
            $stat instanceof PrimaryAttribute => 'Attributes',
            $stat instanceof Resistance, $stat instanceof Boost, $stat instanceof HealingIntake => 'Modifiers',
            default => 'Combat',
        };
    }

    private static function name(Stat $stat): string
    {
        $parts = explode('\\', $stat::class);
        $words = trim((string) preg_replace('/(?<!^)[A-Z]/', replacement: ' $0', subject: end($parts)));

        return str_replace(search: 'Dot ', replace: 'DoT ', subject: $words);
    }

    private static function shown(Stat $stat): string
    {
        if ($stat instanceof NonNegativeInteger) {
            return number_format($stat->value);
        }

        $value = property_exists($stat, 'value') ? (float) $stat->value : 0.0;
        $counted = $stat instanceof Haste || $stat instanceof Resistance ? $stat->effective() : $value;
        $percent = rtrim(rtrim(number_format($counted * 100, decimals: 1), characters: '0'), characters: '.') . '%';

        return $counted < $value ? "{$percent} (capped)" : $percent;
    }
}
