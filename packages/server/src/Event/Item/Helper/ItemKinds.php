<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Item\Helper;

use Lorekeeper\Core\Item\Equipment\Armor;
use Lorekeeper\Core\Item\Equipment\Cape;
use Lorekeeper\Core\Item\Equipment\ClassItem;
use Lorekeeper\Core\Item\Equipment\GroundItem;
use Lorekeeper\Core\Item\Equipment\Helm;
use Lorekeeper\Core\Item\Equipment\Necklace;
use Lorekeeper\Core\Item\Equipment\Pet;
use Lorekeeper\Core\Item\Equipment\Weapon\DamageVariance;
use Lorekeeper\Core\Item\Equipment\Weapon\Weapon;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponDamage;
use Lorekeeper\Core\Item\Equipment\Weapon\WeaponType;
use Lorekeeper\Core\Item\House\HouseItem;
use Lorekeeper\Core\Item\House\HouseItemType;
use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Boost;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Exception\MalformedEventException;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class ItemKinds
{
    private const int STEADINESS_THE_GAME_ASSUMES = 10;

    /**
     * @param array<array-key, mixed> $entry
     * @param class-string<Event> $event
     * @param array<array-key, mixed> $procs
     *
     * @throws MalformedEventException
     */
    public static function read(array $entry, string $event, array $procs = []): Item
    {
        try {
            $data = Type\shape([
                'ItemID' => Type\positive_int(),
                'sName' => Type\non_empty_string(),
                'sType' => Type\optional(Type\string()),
                'iLvl' => Type\int_range(min: 1, max: 100),
            ], allowUnknownFields: true)->coerce($entry);
        } catch (CoercionException $exception) {
            throw new MalformedEventException($event, 'an item has no id, name or level', $exception);
        }

        $id = new ItemId($data['ItemID']);
        $name = new ItemName($data['sName']);
        $level = new ItemLevel($data['iLvl']);
        $type = $data['sType'] ?? '';
        $weapon = self::weaponType($type);
        if ($weapon !== null) {
            return self::weapon($id, $name, $level, $weapon, $entry, $procs, $event);
        }

        return match ($type) {
            'Class' => new ClassItem($id, $name, $level, Enhancements::applied($entry, $procs, $event)),
            'Armor' => new Armor($id, $name, $level),
            'Helm' => new Helm($id, $name, $level, Enhancements::applied($entry, $procs, $event)),
            'Cape' => new Cape($id, $name, $level, Enhancements::applied($entry, $procs, $event)),
            'Pet' => new Pet($id, $name, $level),
            'Necklace' => new Necklace($id, $name, $level),
            'Misc' => new GroundItem($id, $name, $level),
            'Floor Item' => new HouseItem($id, $name, $level, HouseItemType::Floor),
            'Wall Item' => new HouseItem($id, $name, $level, HouseItemType::Wall),
            'House' => new HouseItem($id, $name, $level, HouseItemType::House),
            'ServerUse' => new Boost($id, $name, $level),
            'Enhancement' => Enhancements::sold($id, $name, $level, $entry, $event),
            'Item', 'Note', 'Quest Item', 'Resource' => new Resource($id, $name, $level),
            default => throw new MalformedEventException($event, "unknown item type '{$type}'"),
        };
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

    /**
     * @param array<array-key, mixed> $entry
     * @param array<array-key, mixed> $procs
     * @param class-string<Event> $event
     *
     * @throws MalformedEventException
     *
     * @mago-expect lint:excessive-parameter-list
     */
    private static function weapon(
        ItemId $id,
        ItemName $name,
        ItemLevel $level,
        WeaponType $type,
        array $entry,
        array $procs,
        string $event,
    ): Weapon {
        try {
            $variance =
                Type\shape(['iRng' => Type\optional(Type\nullable(Type\int_range(
                    min: 0,
                    max: 100,
                )))], allowUnknownFields: true)->coerce($entry)['iRng'] ?? self::STEADINESS_THE_GAME_ASSUMES;
        } catch (CoercionException $exception) {
            throw new MalformedEventException(
                $event,
                "weapon {$id->value} varies its damage by an invalid amount",
                $exception,
            );
        }

        return new Weapon(
            $id,
            $name,
            $level,
            $type,
            new WeaponDamage(new DamageVariance($variance)),
            Enhancements::applied($entry, $procs, $event),
        );
    }
}
