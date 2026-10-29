<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Facts\Reading;

use Lorekeeper\Apps\Wiki\Facts\Fact;
use Lorekeeper\Apps\Wiki\Facts\Kind;
use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\CharacterClass\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\House\HouseItemType;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\ItemTag;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemFacts
{
    /** @return list<Fact<ItemId, object>> */
    public static function of(Entry $entry): array
    {
        $id = $entry->int('ItemID');
        if ($id === null || $id < 1) {
            return [];
        }

        $name = $entry->text('sName');
        $values = [
            ...array_values(array_filter([
                $name === null ? null : new ItemName($name),
                $entry->flag('bUpg') ? ItemTag::MemberOnly : null,
                $entry->flag('bTemp') ? ItemTag::Temporary : null,
            ])),
            ...self::kind($entry->text('sType') ?? '', $entry),
        ];

        $id = new ItemId($id);

        return array_map(static fn(object $value): Fact => new Fact($id, $value), $values);
    }

    /** @return list<object> */
    private static function kind(string $type, Entry $entry): array
    {
        $weapon = self::weaponType($type);
        if ($weapon !== null) {
            return [new Kind(Weapon::class), $weapon, ...self::variance($entry)];
        }

        $house = match ($type) {
            'Floor Item' => HouseItemType::Floor,
            'Wall Item' => HouseItemType::Wall,
            'House' => HouseItemType::House,
            default => null,
        };
        if ($house !== null) {
            return [new Kind(HouseItem::class), $house];
        }

        return match ($type) {
            'Armor' => [new Kind(Armor::class)],
            'Helm' => [new Kind(Helm::class)],
            'Cape' => [new Kind(Cape::class)],
            'Pet' => [new Kind(Pet::class)],
            'Misc' => [new Kind(GroundItem::class)],
            'Class' => [new Kind(ClassItem::class)],
            'ServerUse' => [new Kind(Boost::class), ...self::stack($entry)],
            'Item', 'Note', 'Quest Item', 'Resource' => [new Kind(Resource::class), ...self::stack($entry)],
            default => [],
        };
    }

    /** @return list<DamageVariance> */
    private static function variance(Entry $entry): array
    {
        $variance = $entry->int('iRng');

        return $variance === null || $variance < 0 || $variance > 100 ? [] : [new DamageVariance($variance)];
    }

    /** @return list<Quantity> */
    private static function stack(Entry $entry): array
    {
        $stack = $entry->int('iStk');

        return $stack === null || $stack < 1 ? [] : [new Quantity($stack)];
    }

    private static function weaponType(string $type): ?WeaponType
    {
        return match ($type) {
            'Axe' => WeaponType::Axe,
            'Bow' => WeaponType::Bow,
            'Dagger' => WeaponType::Dagger,
            'Gauntlet' => WeaponType::Gauntlet,
            'Gun' => WeaponType::Gun,
            'HandGun' => WeaponType::HandGun,
            'Mace' => WeaponType::Mace,
            'Polearm' => WeaponType::Polearm,
            'Rifle' => WeaponType::Rifle,
            'Staff' => WeaponType::Staff,
            'Sword' => WeaponType::Sword,
            'Wand' => WeaponType::Wand,
            'Whip' => WeaponType::Whip,
            default => null,
        };
    }
}
