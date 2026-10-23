<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest\Helper;

use Lorekeeper\Core\World\Quest\QuestGroup;
use Lorekeeper\Core\World\Quest\QuestGroupId;
use Lorekeeper\Core\World\Quest\QuestGroupNumber;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class QuestGroups
{
    private const array FIELDS = ['id0', 'id1', 'id2', 'id3', 'id4', 'id5', 'id6', 'id7', 'iw0', 'im0'];

    private const int BITS_PER_FIELD = 32;

    /** @return Option\Option<QuestGroupId> */
    public static function of(string $field, int $index): Option\Option
    {
        $group = self::group($field);
        $number = substr($field, offset: 2);
        if ($group === null || $index < 0 || $index >= self::BITS_PER_FIELD || !ctype_digit($number)) {
            return Option\none();
        }

        return Option\some(
            new QuestGroupId($group, new QuestGroupNumber(((int) $number * self::BITS_PER_FIELD) + $index)),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws CoercionException
     *
     * @return list<QuestGroupId>
     */
    public static function held(array $data): array
    {
        $held = [];
        foreach (self::FIELDS as $field) {
            $flags = Type\uint()->coerce($data[$field] ?? 0);
            for ($index = 0; $index < self::BITS_PER_FIELD; $index++) {
                if ((($flags >> $index) & 1) !== 1) {
                    continue;
                }

                $held[] = self::of($field, $index)->unwrap();
            }
        }

        return $held;
    }

    private static function group(string $field): ?QuestGroup
    {
        return match (substr($field, offset: 0, length: 2)) {
            'id' => QuestGroup::Daily,
            'iw' => QuestGroup::Weekly,
            'im' => QuestGroup::Monthly,
            default => null,
        };
    }
}
