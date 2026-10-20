<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Item\Helper;

use Lorekeeper\Core\Item\EquipmentSlot;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Enhancement\EffectDescription;
use Lorekeeper\Core\Item\Misc\Enhancement\EffectName;
use Lorekeeper\Core\Item\Misc\Enhancement\Enhancement;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementEffect;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementItem;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementRarity;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class Enhancements
{
    /**
     * @param class-string<Event> $event
     * @param array<array-key, mixed> $entry
     * @param array<array-key, mixed> $procs
     *
     * @throws MalformedEventException
     *
     * @return Option\Option<Enhancement>
     */
    public static function applied(array $entry, array $procs, string $event): Option\Option
    {
        try {
            $data = Type\shape([
                'EnhPatternID' => Type\optional(Type\nullable(Type\int())),
                'EnhLvl' => Type\optional(Type\nullable(Type\int_range(min: 0, max: 100))),
                'EnhRty' => Type\optional(Type\nullable(Type\int())),
                'ProcID' => Type\optional(Type\nullable(Type\int())),
            ], allowUnknownFields: true)->coerce($entry);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, 'an enhancement on an item is invalid', $exception);
        }

        $type = self::type($data['EnhPatternID'] ?? 0);
        $rarity = self::rarity($data['EnhRty'] ?? 0);
        $level = $data['EnhLvl'] ?? 0;
        if ($type === null || $rarity === null || $level < 1) {
            return Option\none();
        }

        return Option\some(
            new Enhancement($type, $rarity, new ItemLevel($level), self::effect($data['ProcID'] ?? 0, $procs, $event)),
        );
    }

    /**
     * @param class-string<Event> $event
     * @param array<array-key, mixed> $entry
     *
     * @throws MalformedEventException
     */
    public static function sold(
        ItemId $id,
        ItemName $name,
        ItemLevel $level,
        array $entry,
        string $event,
    ): EnhancementItem {
        try {
            $data = Type\shape([
                'PatternID' => Type\int(),
                'iRty' => Type\int(),
                'sES' => Type\string(),
            ], allowUnknownFields: true)->coerce($entry);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, 'an enhancement has no type, rarity or slot', $exception);
        }

        $type = self::type($data['PatternID']);
        $rarity = self::rarity($data['iRty']);
        if ($type === null || $rarity === null) {
            throw new MalformedEventException(
                $event,
                "enhancement {$id->value} has the unknown type {$data['PatternID']} or rarity {$data['iRty']}",
            );
        }

        return new EnhancementItem(
            $id,
            $name,
            new Enhancement($type, $rarity, $level, Option\none()),
            self::slot($data['sES'], $event),
        );
    }

    private static function type(int $type): ?EnhancementType
    {
        return match ($type) {
            1 => EnhancementType::Adventurer,
            2 => EnhancementType::Fighter,
            3 => EnhancementType::Thief,
            5 => EnhancementType::Hybrid,
            6 => EnhancementType::Wizard,
            7 => EnhancementType::Healer,
            8 => EnhancementType::SpellBreaker,
            9 => EnhancementType::Lucky,
            default => null,
        };
    }

    private static function rarity(int $rarity): ?EnhancementRarity
    {
        return match ($rarity) {
            1 => EnhancementRarity::PlusZero,
            2 => EnhancementRarity::PlusOne,
            3 => EnhancementRarity::PlusTwo,
            4 => EnhancementRarity::PlusThree,
            5 => EnhancementRarity::PlusFour,
            6 => EnhancementRarity::PlusFive,
            7 => EnhancementRarity::PlusSix,
            8 => EnhancementRarity::PlusOneHundred,
            9 => EnhancementRarity::PlusTwoHundred,
            default => null,
        };
    }

    /**
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     */
    private static function slot(string $slot, string $event): EquipmentSlot
    {
        return match ($slot) {
            'Weapon' => EquipmentSlot::Weapon,
            'ar' => EquipmentSlot::CharacterClass,
            'he' => EquipmentSlot::Helm,
            'ba' => EquipmentSlot::Cape,
            default => throw new MalformedEventException($event, "an enhancement fits the unknown slot '{$slot}'"),
        };
    }

    /**
     * @param array<array-key, mixed> $procs
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @return Option\Option<EnhancementEffect>
     */
    private static function effect(int $proc, array $procs, string $event): Option\Option
    {
        if ($proc < 1 || !array_key_exists($proc, $procs)) {
            return Option\none();
        }

        try {
            $data = Type\shape([
                'nam' => Type\non_empty_string(),
                'desc' => Type\non_empty_string(),
            ], allowUnknownFields: true)->coerce($procs[$proc]);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, "effect {$proc} has no name or description", $exception);
        }

        return Option\some(new EnhancementEffect(new EffectName($data['nam']), new EffectDescription($data['desc'])));
    }
}
