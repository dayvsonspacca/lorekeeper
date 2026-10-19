<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest\Helper;

use Lorekeeper\Core\World\Quest\QuestMark;
use Lorekeeper\Core\World\Quest\QuestPeriod;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class QuestMarks
{
    private const array FIELDS = ['id0', 'id1', 'id2', 'id3', 'id4', 'id5', 'id6', 'id7', 'iw0', 'im0'];

    /** @return Option\Option<QuestMark> */
    public static function of(string $field, int $index): Option\Option
    {
        $period = self::period($field);
        $group = substr($field, offset: 2);
        if ($period === null || $index < 0 || !ctype_digit($group)) {
            return Option\none();
        }

        return Option\some(new QuestMark($period, (int) $group, $index));
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws CoercionException
     *
     * @return list<QuestMark>
     */
    public static function held(array $data): array
    {
        $marks = [];
        foreach (self::FIELDS as $field) {
            $flags = Type\uint()->coerce($data[$field] ?? 0);
            for ($index = 0; $index < 32; $index++) {
                if ((($flags >> $index) & 1) !== 1) {
                    continue;
                }

                $marks[] = self::of($field, $index)->unwrap();
            }
        }

        return $marks;
    }

    private static function period(string $field): ?QuestPeriod
    {
        return match (substr($field, offset: 0, length: 2)) {
            'id' => QuestPeriod::Day,
            'iw' => QuestPeriod::Week,
            'im' => QuestPeriod::Month,
            default => null,
        };
    }
}
